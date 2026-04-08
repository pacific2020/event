<?php

namespace App\Jobs;

use App\Models\InvitationCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use setasign\Fpdi\Fpdi;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Common\EccLevel;

class ProcessInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $cardIds;

    public function __construct($email, $cardIds)
    {
        $this->email = $email;
        $this->cardIds = $cardIds;
    }

    public function handle()
    {
        $cards = InvitationCard::whereIn('id', $this->cardIds)->get();

        $templatePath = public_path('storage/templates/Graduation_Invitation_final.pdf');
        $savePath = public_path('storage/invitations/');
        $qrPathDir = public_path('qr_codes/');

        if (!file_exists($savePath)) mkdir($savePath, 0775, true);
        if (!file_exists($qrPathDir)) mkdir($qrPathDir, 0775, true);

        $attachments = [];

        foreach ($cards as $card) {

            $pdfFileName = 'Invitation_' . $card->secret_key . '.pdf';
            $fullFilePath = $savePath . $pdfFileName;

            // ✅ ALWAYS DELETE OLD FILE (FIX)
            if (file_exists($fullFilePath)) {
                unlink($fullFilePath);
            }

            // ✅ GENERATE QR
            $qrFile = $qrPathDir . $card->secret_key . '.png';

            $options = new QROptions([
                'version' => 5,
                'outputInterface' => QRGdImagePNG::class,
                'eccLevel' => EccLevel::H,
                'scale' => 10,
                'imageBase64' => false,
            ]);

            (new QRCode($options))->render($card->secret_key, $qrFile);

            // ✅ GENERATE PDF
            $pdf = new Fpdi();
            $pdf->setSourceFile($templatePath);
            $tplId = $pdf->importPage(1);

            $pdf->AddPage();
            $pdf->useTemplate($tplId, 0, 0, null, null, true);

            $pdf->SetFont('Helvetica', 'B', 22);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetXY(0, 140);

            $pdf->Cell(0, 10, strtoupper($card->fullname), 0, 1, 'C');

            $pdf->Image($qrFile, 85, 195, 40, 40);

            $pdfContent = $pdf->Output('S');

            // ✅ SAVE NEW FILE
            file_put_contents($fullFilePath, $pdfContent);

            // ✅ DELETE TEMP QR
            if (file_exists($qrFile)) {
                unlink($qrFile);
            }

            // ✅ PREPARE EMAIL ATTACHMENT
            $attachments[] = [
                'data' => $pdfContent,
                'name' => 'Invitation_' . str_replace(' ', '_', $card->fullname) . '.pdf'
            ];

            // ✅ UPDATE DB
            $card->update([
                'pdf' => $pdfFileName
            ]);
        }

        // ✅ SEND EMAIL
        Mail::send('emails.invitation_notification', [], function ($message) use ($attachments) {
            $message->to($this->email)
                    ->subject('Official Graduation Invitations');

            foreach ($attachments as $file) {
                $message->attachData($file['data'], $file['name'], [
                    'mime' => 'application/pdf'
                ]);
            }
        });

        // ✅ UPDATE STATUS
        InvitationCard::whereIn('id', $this->cardIds)->update([
            'status' => 'sent',
            'date_generated' => now()
        ]);
    }
}
