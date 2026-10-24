<?php
declare(strict_types=1);

namespace App;

/**
 * Minimal SMTP client — STARTTLS or implicit SSL, AUTH LOGIN, single recipient,
 * HTML body. No attachments. Throws on any protocol-level failure.
 *
 * Sufficient for transactional email through any standard SMTP relay
 * (Gmail/Workspace, Postmark, SendGrid, hosting-provider relays).
 * For attachments / multiple recipients, swap in PHPMailer.
 */
final class Smtp
{
    public static function send(
        string $host,
        int $port,
        string $username,
        string $password,
        string $secure,        // 'tls' (STARTTLS), 'ssl' (implicit), or '' (none)
        string $fromEmail,
        string $fromName,
        string $to,
        string $subject,
        string $htmlBody,
        int $timeout = 30
    ): void {
        $protocol = $secure === 'ssl' ? 'ssl://' : '';
        $sock = @stream_socket_client(
            $protocol . $host . ':' . $port,
            $errno, $errstr, $timeout,
            STREAM_CLIENT_CONNECT
        );
        if (!$sock) {
            throw new \RuntimeException("SMTP connect failed [$host:$port]: $errstr ($errno)");
        }
        stream_set_timeout($sock, $timeout);

        try {
            self::expect($sock, '220');

            $hostname = $_SERVER['SERVER_NAME'] ?? gethostname() ?? 'localhost';
            self::write($sock, "EHLO $hostname");
            self::expect($sock, '250');

            if ($secure === 'tls') {
                self::write($sock, 'STARTTLS');
                self::expect($sock, '220');
                $crypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
                        | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                if (!@stream_socket_enable_crypto($sock, true, $crypto)) {
                    throw new \RuntimeException('SMTP STARTTLS upgrade failed');
                }
                self::write($sock, "EHLO $hostname");
                self::expect($sock, '250');
            }

            if ($username !== '') {
                self::write($sock, 'AUTH LOGIN');
                self::expect($sock, '334');
                self::write($sock, base64_encode($username));
                self::expect($sock, '334');
                self::write($sock, base64_encode($password));
                self::expect($sock, '235');
            }

            self::write($sock, "MAIL FROM:<$fromEmail>");
            self::expect($sock, '250');
            self::write($sock, "RCPT TO:<$to>");
            self::expect($sock, '250');
            self::write($sock, 'DATA');
            self::expect($sock, '354');

            $messageId = bin2hex(random_bytes(12)) . '@' . $hostname;
            $headers = [
                'From: ' . self::encodeAddress($fromName, $fromEmail),
                'To: <' . $to . '>',
                'Subject: ' . self::encodeHeader($subject),
                'Date: ' . date('r'),
                'Message-ID: <' . $messageId . '>',
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];
            // RFC 5321 dot-stuffing
            $body = preg_replace('/^\./m', '..', $htmlBody) ?? $htmlBody;

            fwrite($sock, implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n");
            self::expect($sock, '250');

            self::write($sock, 'QUIT');
        } finally {
            @fclose($sock);
        }
    }

    private static function write($sock, string $line): void
    {
        fwrite($sock, $line . "\r\n");
    }

    private static function expect($sock, string $code): string
    {
        $line = '';
        do {
            $part = fgets($sock, 1024);
            if ($part === false) {
                throw new \RuntimeException('SMTP read failed (timeout or closed connection)');
            }
            $line = $part;
        } while (isset($line[3]) && $line[3] === '-');

        if (substr($line, 0, 3) !== $code) {
            throw new \RuntimeException('SMTP expected ' . $code . ', got: ' . trim($line));
        }
        return $line;
    }

    private static function encodeHeader(string $value): string
    {
        // Always encode for safety (UTF-8 headers).
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private static function encodeAddress(string $name, string $email): string
    {
        return self::encodeHeader($name) . ' <' . $email . '>';
    }
}
