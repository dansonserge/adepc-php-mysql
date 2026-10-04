<?php
declare(strict_types=1);

namespace Adepc;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends email with the transport chosen in Settings: SMTP (a cPanel mailbox,
 * recommended), PHP mail(), or none — in which case callers fall back to a
 * pre-filled mailto: link, as the original site did without Resend.
 */
final class Mailer
{
    public static function configured(): bool
    {
        $t = Site::setting('mail.transport', 'none');
        return ($t === 'smtp' && Site::setting('mail.smtp_host') !== '' && Site::setting('mail.from_address') !== '')
            || ($t === 'mail' && Site::setting('mail.from_address') !== '');
    }

    /** @throws \Throwable when delivery fails */
    public static function send(string $to, string $subject, string $body, ?string $replyTo = null): void
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        if (Site::setting('mail.transport') === 'smtp') {
            $mail->isSMTP();
            $mail->Host = Site::setting('mail.smtp_host');
            $mail->Port = (int) Site::setting('mail.smtp_port', '465');
            $secure = Site::setting('mail.smtp_secure', 'ssl');
            $mail->SMTPSecure = $secure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : ($secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : '');
            $mail->SMTPAutoTLS = $secure !== 'none';
            if (Site::setting('mail.smtp_user') !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = Site::setting('mail.smtp_user');
                $mail->Password = Site::setting('mail.smtp_pass');
            }
            $mail->Timeout = 15;
        } else {
            $mail->isMail();
        }
        $mail->setFrom(Site::setting('mail.from_address'), Site::setting('mail.from_name'));
        foreach (preg_split('/[,;\s]+/', $to) ?: [] as $address) {
            if ($address !== '') {
                $mail->addAddress($address);
            }
        }
        if ($replyTo) {
            $mail->addReplyTo($replyTo);
        }
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML(false);
        $mail->send();
    }
}
