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

// Chillerlan QR Code namespaces
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;

class ExcelController extends Controller
{
  public function sendInvitations()
    {

        // Increase time limit for large Excel files
        set_time_limit(600);

        $path = public_path('storage/exel/guestinformation.xlsx');
        $qrDir = public_path('storage/qrcodes');
        $pdfDir = public_path('storage/invitations');
        $templatePath = public_path('storage/templates/Graduation_Invitation_final.pdf');

        // Ensure directories exist
        if (!File::exists($qrDir)) File::makeDirectory($qrDir, 0755, true);
        if (!File::exists($pdfDir)) File::makeDirectory($pdfDir, 0755, true);

        if (!File::exists($path)) {
            return response()->json(['status' => 'error', 'message' => 'Excel file not found.']);
        }

        try {
            $spreadsheet = IOFactory::load($path);
            $rows = $spreadsheet->getActiveSheet()->toArray();

            // Configure PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'mail.rp.ac.rw';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'cyprien@rp.ac.rw';
            $mail->Password   = 'P@sswOrD!123';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->setFrom('dontreply@rp.ac.rw', 'Graduation Committee');

         // Configure Chillerlan QR Options
            $options = new QROptions([
                'version'         => 5,
                'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
                'eccLevel'        => \chillerlan\QRCode\Common\EccLevel::H, // Using full path to be safe
                'scale'           => 5,
                'imageBase64'     => false,
            ]);

            $successCount = 0;

            foreach (array_slice($rows, 1) as $index => $row) {
    // Basic row validation
    if (!isset($row[0]) || empty(trim($row[0]))) continue;

    $name  = trim($row[0]);
    $email = isset($row[1]) ? trim($row[1]) : null;

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Log::warning("Invalid email at row " . ($index + 2));
        continue;
    }

    // 1. CORRECTED CHECK: Check if email already exists in the database
    $alreadyExists = InvitationCard::where('email', $email)->exists();

    if ($alreadyExists) {
        Log::info("Skipping $email: Invitation already sent.");
            return response()->json([
                'status' => 'error',
                'message' => "Skipping $email: Invitation already sent."
            ]);
        continue; // Move to the next person in the Excel sheet
    }

    try {
        $secret_key = strtoupper(uniqid('INV-'));
        $qrPath = $qrDir . '/' . $secret_key . '.png';

        // --- CHILLERLAN QR GENERATION ---
        (new QRCode($options))->render($secret_key, $qrPath);

        // --- PDF GENERATION (FPDI) ---
        $pdf = new Fpdi();
        $pdf->AddPage();
        if (!File::exists($templatePath)) throw new \Exception("PDF Template missing.");

        $pdf->setSourceFile($templatePath);
        $pdf->useTemplate($pdf->importPage(1));

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetXY(40, 120);
        $pdf->Cell(100, 50, $name, 0, 0, 'C');

        // Place QR on PDF
        $pdf->Image($qrPath, 90, 250, 35, 35);

        $pdfFileName = $secret_key . '.pdf';
        $pdfPath = $pdfDir . '/' . $pdfFileName;
        $pdf->Output($pdfPath, 'F');

        // --- SAVE TO DATABASE ---
        InvitationCard::create([
            'fullname' => $name,
            'position' => $name,
            'email'    => $email,
            'secret_key' => $secret_key,
            'status'   => 'SENT',
            'pdf'      => $pdfFileName,
            'date_generated' => now()
        ]);

        // --- SEND EMAIL ---
        $mail->clearAddresses();
        $mail->clearAttachments();
        $mail->addAddress($email);
        $mail->Subject = 'Official Graduation Invitation';
        $mail->isHTML(true);
        $mail->Body = "Dear <b>{$name}</b>,<br><br>Attached is your digital invitation card for the graduation ceremony.";
        $mail->addAttachment($pdfPath);
        $mail->send();

        // 2. STORAGE CLEANUP: Delete files after sending to save space
        if (File::exists($qrPath)) File::delete($qrPath);
        if (File::exists($pdfPath)) File::delete($pdfPath);

        $successCount++;

    } catch (\Exception $e) {
        Log::error("Error for {$email}: " . $e->getMessage());

        // Ensure cleanup happens even if sending fails
        if (isset($qrPath) && File::exists($qrPath)) File::delete($qrPath);
        if (isset($pdfPath) && File::exists($pdfPath)) File::delete($pdfPath);
    }
}

            return response()->json([
                'status' => 'success',
                'message' => "Successfully sent $successCount invitations."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'System Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function viewGuestInvitation(Request $request)
    {
        $query = InvitationCard::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('phonenumber', 'like', "%{$search}%");
            });
        }

        $perPage = 10;
        $guests = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($guests);
    }

    public function deleteGuest($id)
    {
        $guest = InvitationCard::find($id);
        if (!$guest) {
            return response()->json(['message' => 'Guest not found'], 404);
        }
        $guest->delete();
        return response()->json(['message' => 'Guest deleted successfully']);
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
