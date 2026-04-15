<?php

namespace App\Jobs;

use App\Models\InvitationCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail; // ✅ Essential for Laravel Mail
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Common\EccLevel;

class SendInvitationGuestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rowData;
    protected $confirmUrl;

    public function __construct($rowData)
    {
        $this->rowData = $rowData;
        $this->confirmUrl = config('app.frontend_url', env('FRONTEND_URL')) . '/guest/confirmation';
    }

    public function handle()
    {
        $name = trim($this->rowData[0]);
        $email = trim($this->rowData[1]);

        if (InvitationCard::where('email', $email)->exists()) {
            return;
        }

        $qrDir = storage_path('app/public/qrcodes');
        $pdfDir = storage_path('app/public/invitations');
        $templatePath = storage_path('app/public/templates/Graduation_Invitation_final.pdf');

        if (!File::exists($qrDir)) File::makeDirectory($qrDir, 0755, true);
        if (!File::exists($pdfDir)) File::makeDirectory($pdfDir, 0755, true);

        $secret_key = strtoupper(uniqid('INV-'));
        $qrPath = $qrDir . '/' . $secret_key . '.png';
        $pdfFileName = $secret_key . '.pdf';
        $pdfPath = $pdfDir . '/' . $pdfFileName;

        try {
            // 1. Generate QR
            $options = new QROptions([
                'version' => 5,
                'outputInterface' => QRGdImagePNG::class,
                'eccLevel' => EccLevel::H,
                'scale' => 5,
            ]);
            (new QRCode($options))->render($secret_key, $qrPath);

            // 2. Generate PDF
            $pdf = new Fpdi();
            $pdf->AddPage();
            if (!File::exists($templatePath)) throw new \Exception("Template missing.");
            $pdf->setSourceFile($templatePath);
            $pdf->useTemplate($pdf->importPage(1));
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->SetXY(40, 120);
            $pdf->Cell(100, 50, $name, 0, 0, 'C');
            $pdf->Image($qrPath, 90, 250, 35, 35);
            $pdf->Output($pdfPath, 'F');

            // 3. Send Email using Laravel Mail Facade
            $this->sendEmail($email, $name, $pdfPath);

            // 4. Save to DB
            InvitationCard::create([
                'fullname' => $name,
                'email' => $email,
                'secret_key' => $secret_key,
                'status' => 'Sent',
                'pdf' => $pdfFileName,
                'date_generated' => now()
            ]);

            if (File::exists($qrPath)) File::delete($qrPath);

        } catch (\Exception $e) {
            Log::error("Job Failed for {$email}: " . $e->getMessage());
            if (File::exists($qrPath)) File::delete($qrPath);
            if (File::exists($pdfPath)) File::delete($pdfPath);
            throw $e;
        }
    }

    private function sendEmail($email, $name, $attachment)
    {
        try {
            Log::info("Attempting Laravel Mail to: {$email}");

            $data = [
                'name' => $name,
                'email' => $email,
                'confirmUrl' => $this->confirmUrl
            ];

            Mail::send('emails.invitation_guest', $data, function ($message) use ($email, $attachment) {
                $message->to($email)
                        ->subject('Official Graduation Invitation');

                if (File::exists($attachment)) {
                    $message->attach($attachment);
                }
            });

            Log::info("Laravel Mail successfully sent to: {$email}");

        } catch (\Exception $e) {
            Log::error("Laravel Mail Error for {$email}: " . $e->getMessage());
            throw $e;
        }
    }
}
