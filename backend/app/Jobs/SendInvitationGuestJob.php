<?php

namespace App\Jobs;

use App\Models\InvitationCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
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
        $this->confirmUrl = url('/guest/confirmation');


    }

    public function handle()
    {
        try {

            $position = trim($this->rowData[0] ?? '');
            $fullname = trim($this->rowData[1] ?? '');
            $email    = trim($this->rowData[2] ?? '');
            $phone    = trim($this->rowData[3] ?? '');
            $type     = trim($this->rowData[4] ?? '');

            if (empty($email)) {
                throw new \Exception("Email is empty");
            }

            if (InvitationCard::where('email', $email)->exists()) {
                Log::info("Skipped existing email: {$email}");
                return;
            }

            $qrDir        = storage_path('app/public/qrcodes');
            $pdfDir       = storage_path('app/public/invitations');
            $templatePath = storage_path('app/public/templates/guest_invitation.pdf');

            if (!File::exists($qrDir)) {
                File::makeDirectory($qrDir, 0755, true);
            }

            if (!File::exists($pdfDir)) {
                File::makeDirectory($pdfDir, 0755, true);
            }

            if (!File::exists($templatePath)) {
                throw new \Exception("Template missing: {$templatePath}");
            }

            $secret_key = strtoupper(uniqid('INV-'));
            $qrPath = $qrDir . '/' . $secret_key . '.png';
            $pdfFileName = $secret_key . '.pdf';
            $pdfPath = $pdfDir . '/' . $pdfFileName;

            /*
            |--------------------------------------------------------------------------
            | QR CODE GENERATION
            |--------------------------------------------------------------------------
            */
            $options = new QROptions([
                'version' => 5,
                'outputInterface' => QRGdImagePNG::class,
                'eccLevel' => EccLevel::H,
                'scale' => 5,
            ]);

            (new QRCode($options))->render($secret_key, $qrPath);

            Log::info("QR generated: {$qrPath}");

            /*
            |--------------------------------------------------------------------------
            | PDF GENERATION (ALL PAGES FIXED)
            |--------------------------------------------------------------------------
            */
            $pdf = new Fpdi();

            $pageCount = $pdf->setSourceFile($templatePath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {

                $tplId = $pdf->importPage($pageNo);

                $size = $pdf->getTemplateSize($tplId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);

                $pdf->useTemplate($tplId);

                // Only write content on first page
                if ($pageNo == 1) {

                    $pdf->SetFont('Arial', '', 12);
                    $pdf->SetXY(40, 153);
                    $pdf->Cell(130, 10, $position, 0, 0, 'C');


                    $pdf->Image($qrPath, 90, 250, 30, 30);
                }
            }

            $pdf->Output($pdfPath, 'F');

            Log::info("PDF generated: {$pdfPath}");

            /*
            |--------------------------------------------------------------------------
            | EMAIL SENDING
            |--------------------------------------------------------------------------
            */
            $this->sendEmail($email, $position, $fullname, $pdfPath);

            /*
            |--------------------------------------------------------------------------
            | DATABASE SAVE
            |--------------------------------------------------------------------------
            */
            InvitationCard::create([
                'position'       => $position,
                'fullname'       => $fullname,
                'email'          => $email,
                'secret_key'     => $secret_key,
                'status'         => 'Sent',
                'phonenumber'    => $phone,
                'type'           => $type,
                'pdf'            => $pdfFileName,
                'date_generated' => now(),
            ]);

            Log::info("Saved: {$email}");

            /*
            |--------------------------------------------------------------------------
            | CLEANUP
            |--------------------------------------------------------------------------
            */
            if (File::exists($qrPath)) {
                File::delete($qrPath);
            }

        } catch (\Throwable $e) {

            Log::error("JOB FAILED: " . $e->getMessage());
            Log::error("FILE: " . $e->getFile());
            Log::error("LINE: " . $e->getLine());

            if (isset($qrPath) && File::exists($qrPath)) {
                File::delete($qrPath);
            }

            if (isset($pdfPath) && File::exists($pdfPath)) {
                File::delete($pdfPath);
            }

            throw $e;
        }
    }

    private function sendEmail($email, $position, $fullname, $attachment)
    {
        try {

            $confirmUrl = $this->confirmUrl . '?email=' . urlencode($email);

            $data = [
                'name'       => $fullname,
                'position'   => $position,
                'email'      => $email,
                'confirmUrl' => $confirmUrl,
            ];

            Mail::send('emails.invitation_guest', $data, function ($message) use ($email, $attachment) {

                $message->to($email)
                        ->subject('Invitation to RP 9th Graduation Ceremony');

                $committee = "pmutuyimana1@rp.ac.rw";

                if (!empty($committee)) {
                    $message->cc($committee);
                }

                if (File::exists($attachment)) {
                    $message->attach($attachment);
                }
            });

            Log::info("Mail sent: {$email}");

        } catch (\Throwable $e) {
            Log::error("MAIL ERROR: " . $e->getMessage());
            throw $e;
        }
    }
}
