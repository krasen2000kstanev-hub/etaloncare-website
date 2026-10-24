<?php
declare(strict_types=1);

namespace App;

/**
 * Outgoing email. Uses PHP mail() in Phase 2 for simplicity. SMTP via the
 * settings table (smtp_host etc.) is wired up in Phase 3 — switching is
 * transparent to callers; just call Mailer::send().
 *
 * All sends are logged to email_log for debugging and admin re-sending.
 */
final class Mailer
{
    /**
     * Render an email template + send. Templates live in app/Emails/<name>.php
     * and have access to $data + an $emailLayout helper that wraps content.
     *
     * @return bool true on accepted send, false on failure (also logged).
     */
    public static function send(
        string $template,
        string $to,
        string $subject,
        array $data = [],
        ?int $orderId = null
    ): bool {
        $body = self::renderTemplate($template, $data);
        $from = self::fromHeader();

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: ' . $from,
            'Reply-To: ' . self::settings()['from_email'],
            'X-Mailer: ETALON',
        ];

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $error = null;
        $success = false;
        $smtp = Settings::getMany(['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_secure']);

        if (!empty($smtp['smtp_host'])) {
            // SMTP path — preferred (better deliverability than mail()).
            try {
                $s = self::settings();
                Smtp::send(
                    host:      $smtp['smtp_host'],
                    port:      (int) ($smtp['smtp_port'] ?: 587),
                    username:  $smtp['smtp_user'] ?? '',
                    password:  $smtp['smtp_pass'] ?? '',
                    secure:    $smtp['smtp_secure'] ?? 'tls',
                    fromEmail: $s['from_email'],
                    fromName:  $s['from_name'],
                    to:        $to,
                    subject:   $subject,
                    htmlBody:  $body
                );
                $success = true;
            } catch (\Throwable $e) {
                $error = 'SMTP: ' . $e->getMessage();
            }
        } else {
            // Fallback: PHP mail() — works on most shared hosting but worse deliverability.
            try {
                $sent = @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
                $success = (bool) $sent;
                if (!$sent) $error = 'mail() returned false — configure SMTP in admin Settings';
            } catch (\Throwable $e) {
                $error = 'mail(): ' . $e->getMessage();
            }
        }

        Db::execute(
            'INSERT INTO email_log (order_id, template, recipient, subject, success, error)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$orderId, $template, $to, $subject, $success ? 1 : 0, $error]
        );

        return $success;
    }

    private static function settings(): array
    {
        static $cached = null;
        if ($cached !== null) return $cached;
        $s = Settings::getMany(['smtp_from_email', 'smtp_from_name']);
        $cached = [
            'from_email' => $s['smtp_from_email'] ?: 'no-reply@etaloncare.bg',
            'from_name'  => $s['smtp_from_name']  ?: 'ÉTALON',
        ];
        return $cached;
    }

    private static function fromHeader(): string
    {
        $s = self::settings();
        $name = '=?UTF-8?B?' . base64_encode($s['from_name']) . '?=';
        return sprintf('%s <%s>', $name, $s['from_email']);
    }

    private static function renderTemplate(string $template, array $data): string
    {
        $file = APP_ROOT . '/Emails/' . basename($template) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Email template not found: {$template}");
        }
        $logoUrl = Media::absoluteUrlFor('logo');
        $brandName = $GLOBALS['__config']['app']['name'] ?? 'ÉTALON';

        // Templates set $emailContent then include _layout.php at the bottom.
        $renderInner = function () use ($file, $data, $logoUrl, $brandName) {
            extract($data, EXTR_SKIP);
            ob_start();
            require $file;
            return ob_get_clean();
        };

        return $renderInner();
    }
}
