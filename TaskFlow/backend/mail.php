<?php
/**
 * TaskFlow — Отправка email (верификация и уведомления)
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

function sendVerificationEmail(string $email, string $code, string $username = ''): bool
{
    $greeting = $username !== '' ? "Здравствуйте, {$username}!" : 'Здравствуйте!';

    $htmlBody = <<<HTML
<p>{$greeting}</p>
<p>Ваш код подтверждения для регистрации в <strong>TaskFlow</strong>:</p>
<p style="font-size:28px;font-weight:bold;letter-spacing:4px;margin:24px 0;">{$code}</p>
<p>Код действителен 1 час. Если вы не регистрировались — просто проигнорируйте это письмо.</p>
HTML;

    $textBody = "{$greeting}\n\nВаш код подтверждения TaskFlow: {$code}\n\nКод действителен 1 час.";

    return sendMail(
        $email,
        $username !== '' ? $username : $email,
        'Код подтверждения TaskFlow',
        $htmlBody,
        $textBody,
        $code
    );
}

function sendMail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $textBody,
    ?string $fallbackCode = null
): bool {
    if (!MAIL_ENABLED) {
        logVerificationFallback($toEmail, $fallbackCode);
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->Port = MAIL_PORT;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;

        if (MAIL_USERNAME !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
        } else {
            $mail->SMTPAuth = false;
        }

        if (MAIL_ENCRYPTION === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (MAIL_ENCRYPTION === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        }

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;

        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('TaskFlow mail error: ' . $mail->ErrorInfo);
        logVerificationFallback($toEmail, $fallbackCode);
        return false;
    }
}

function logVerificationFallback(string $email, ?string $code): void
{
    if ($code !== null) {
        error_log("=== VERIFICATION CODE for {$email}: {$code} ===");
    }
}
