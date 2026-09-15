<?php
namespace Nm;

/**
 * Mail delivery (doc 04 §8): smtp transport in prod (vanilla socket client, STARTTLS/TLS),
 * log transport in dev/tests (writes .eml to storage/mail/). Never exposes credentials.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $html, string $text, string $replyTo = ''): bool
    {
        $headers = self::headers($to, $subject, $replyTo);
        if (cfg('mail.transport') === 'log') {
            $f = nm_path('storage/mail/') . date('Ymd-His-') . bin2hex(random_bytes(4)) . '.eml';
            file_put_contents($f, $headers . "\r\n\r\n" . self::multipart($html, $text));
            return true;
        }
        return self::smtp($to, $subject, $headers, self::multipart($html, $text));
    }

    private static function headers(string $to, string $subject, string $replyTo): string
    {
        [$fromMail, $fromName] = cfg('mail.from');
        $b = bin2hex(random_bytes(16));
        $h = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromMail>\r\n";
        $h .= "To: $to\r\n";
        $h .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
        $h .= "Message-ID: <$b@nilemaple.com>\r\n";
        $h .= "Date: " . date('r') . "\r\n";
        $h .= "MIME-Version: 1.0\r\n";
        if ($replyTo) $h .= "Reply-To: $replyTo\r\n";
        return $h;
    }

    private static function multipart(string $html, string $text): string
    {
        $bound = 'nm-' . bin2hex(random_bytes(8));
        $body = "Content-Type: multipart/alternative; boundary=\"$bound\"\r\n\r\n";
        $body .= "--$bound\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($text)) . "\r\n";
        $body .= "--$bound\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($html)) . "\r\n--$bound--\r\n";
        return $body;
    }

    private static function smtp(string $to, string $subject, string $headers, string $body): bool
    {
        $c = cfg('mail.smtp');
        $errno = 0; $errstr = '';
        $proto = $c['tls'] ? 'ssl' : 'tcp';
        $fp = @stream_socket_client("$proto://{$c['host']}:{$c['port']}", $errno, $errstr, 5);
        if (!$fp) { error_log("[nm-mail] connect fail: $errstr"); return false; }
        stream_set_timeout($fp, 5);
        $rd = static function () use ($fp): string { $s = ''; while ($l = fgets($fp)) { $s .= $l; if (preg_match('/^\d{3} /', $l)) break; } return $s; };
        $wr = static function (string $l) use ($fp): void { fwrite($fp, $l . "\r\n"); };
        rd();
        $wr('EHLO nilemaple.com'); rd();
        if (!$c['tls']) { $wr('STARTTLS'); rd(); stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT); $wr('EHLO nilemaple.com'); rd(); }
        $wr('AUTH LOGIN'); rd();
        $wr(base64_encode($c['user'])); rd();
        $wr(base64_encode($c['pass'])); rd();
        $wr('MAIL FROM:<' . cfg('mail.from')[0] . '>'); rd();
        $wr("RCPT TO:<$to>"); rd();
        $wr('DATA'); rd();
        fwrite($fp, $headers . "\r\n" . $body . "\r\n.\r\n"); rd();
        $wr('QUIT'); fclose($fp);
        return true;
    }
}
