<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use setasign\Fpdi\Fpdi; // ONLY ONE OF THESE
use App\Models\InvitationCard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Jobs\SendInvitationGuestJob;

// Chillerlan QR Code namespaces
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;

class ExcelController extends Controller
{
  public function sendInvitations()
    {
        // 1. Define the path to your Excel file
        $path = public_path('storage/excel/guestinformation.xlsx');

        // 2. Check if the file exists before processing
        if (!File::exists($path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Excel file not found at: ' . $path
            ], 404);
        }

        try {
            // 3. Load the Spreadsheet
            $spreadsheet = IOFactory::load($path);
            $rows = $spreadsheet->getActiveSheet()->toArray();

            // Remove the header row
            $data = array_slice($rows, 1);
            $dispatchCount = 0;

            foreach ($data as $index => $row) {
                // Basic validation: skip if name (row 0) or email (row 1) is missing
                if (empty(trim($row[0])) || empty(trim($row[2]))) {
                    continue;
                }

                $email = trim($row[2]);

                // 4. Duplicate Check: Skip if this email already exists in the database
                if (InvitationCard::where('email', $email)->exists()) {
                    Log::info("Skipping $email: Invitation already exists in database.");
                    continue;
                }

                // 5. Dispatch to Queue: Each row becomes a background task
                // This happens instantly (approx 2ms per row)
                SendInvitationGuestJob::dispatch($row);

                $dispatchCount++;
            }

            return response()->json([
                'status' => 'success',
                'message' => "Successfully queued $dispatchCount invitations for processing.",
                'total_rows_found' => count($data)
            ]);

        } catch (\Exception $e) {
            Log::error("Excel Processing Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process Excel file: ' . $e->getMessage()
            ], 500);
        }
    }

public function viewGuestInvitation(Request $request)
{
    // Start the query with the strict filter first
    $query = InvitationCard::query()->whereNull('reg_no');

    // Handle Search
    if ($request->filled('search')) {
        $search = $request->search;

        // Wrapping in a function ensures the "OR" logic stays inside
        // parentheses and doesn't break the "whereNull" logic.
        $query->where(function($q) use ($search) {
            $q->where('fullname', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('position', 'like', "%{$search}%")
              ->orWhere('phonenumber', 'like', "%{$search}%");
        });
    }

    // Dynamic pagination (Optional: allow frontend to control perPage)
    $perPage = $request->get('per_page', 10);

    // Order and Execute
    $guests = $query->orderBy('id', 'desc')->paginate($perPage);

    return response()->json($guests);
}




public function deleteGuest($id)
{
    $guest = InvitationCard::find($id);

    if (!$guest) {
        return response()->json(['message' => 'Guest not found'], 404);
    }

    // 1. Define the full path to the file
    // Use public_path or storage_path depending on where your files are stored
    $filePath = public_path('storage/invitations/' . $guest->pdf);

    // 2. Delete the physical file first (if it exists)
    if ($guest->pdf && file_exists($filePath)) {
        unlink($filePath);
    }

    // 3. Delete the record from the database
    $guest->delete();

    // 4. Return the response last
    return response()->json(['message' => 'Guest and invitations deleted successfully']);
}

public function sendReminders()
{
    // Increase time limit
    set_time_limit(600);

    // 1. FETCH ONLY GUESTS WITHOUT A REGISTRATION NUMBER
    $guests = InvitationCard::whereNull('reg_no')->get();

    if ($guests->isEmpty()) {
        return response()->json([
            'status' => 'success',
            'message' => 'All guests have already registered. No reminders needed.'
        ]);
    }

    $attachmentPath = public_path('storage/passGate/vvip.jpg');

    // Check if the file exists before starting the loop
    if (!File::exists($attachmentPath)) {
        return response()->json(['status' => 'error', 'message' => 'Attachment vip.jpeg not found.']);
    }

    $mail = new PHPMailer(true);
    $successCount = 0;

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'mail.rp.ac.rw';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cyprien@rp.ac.rw';
        $mail->Password   = 'P@sswOrD!123';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->setFrom('dontreply@rp.ac.rw', 'Graduation Committee');

        foreach ($guests as $guest) {
            try {
                $mail->clearAddresses();
                $mail->clearAttachments();

                $mail->addAddress($guest->email);
                $mail->isHTML(true);
                $mail->Subject = 'URGENT: Graduation Ceremony Reminder';

                $mail->Body = "
                    Dear <b>{$guest->position}</b>,<br><br>
                    We noticed you haven't completed your registration for the graduation ceremony yet.<br><br>
                    <b>Please Note:</b> It is highly recommended to <b>arrive 1 hour before</b> the start of the event.
                    Attached is the VIP guide for your reference.<br><br>
                    We look forward to seeing you there!<br><br>
                    Best Regards,<br>
                    Graduation Committee
                ";

                $mail->addAttachment($attachmentPath, 'VIP_Guide.jpeg');

                $mail->send();
                $successCount++;

            } catch (\Exception $e) {
                Log::error("Reminder failed for {$guest->email}: " . $e->getMessage());
                continue;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Successfully sent reminders to $successCount unregistered guests."
        ]);

    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}


}
