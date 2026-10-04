<?php
declare(strict_types=1);

namespace Adepc\Front;

use Adepc\Cache;
use Adepc\Config;
use Adepc\Http;
use Adepc\Mailer;
use Adepc\Site;

/**
 * The contact form. Messages are emailed, never stored. Works without JS
 * (POST → result page) and with JS (contact.js posts and asks for JSON).
 */
final class Contact
{
    /** Signed render time: part of the bot check, works without JS. */
    public static function stamp(): string
    {
        $t = (string) (int) (microtime(true) * 1000);
        return $t . '.' . substr(hash_hmac('sha256', $t, self::key()), 0, 24);
    }

    private static function key(): string
    {
        return (string) Config::get('app_key', 'adepc');
    }

    private static function stampAge(string $stamp): ?int
    {
        [$t, $sig] = array_pad(explode('.', $stamp, 2), 2, '');
        if ($t === '' || !hash_equals(substr(hash_hmac('sha256', $t, self::key()), 0, 24), $sig)) {
            return null;
        }
        return (int) (microtime(true) * 1000) - (int) $t;
    }

    public static function submit(): void
    {
        $text = static fn (string $k): string => is_string($_POST[$k] ?? null) ? (string) $_POST[$k] : '';
        $purposes = array_column(Site::d()['purposes'], null, 'pkey');
        $values = [
            'name' => trim($text('name')),
            'email' => trim($text('email')),
            'purpose' => $text('purpose'),
            'church' => $text('church'),
            'message' => trim($text('message')),
            'confidential' => ($_POST['confidential'] ?? '') === 'on',
        ];

        $errors = [];
        $len = static fn (string $s): int => mb_strlen($s, 'UTF-8');
        if ($len($values['name']) < 2 || $len($values['name']) > 120) {
            $errors['name'] = 'required';
        }
        if ($values['email'] === '') {
            $errors['email'] = 'required';
        } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || $len($values['email']) > 200) {
            $errors['email'] = 'invalidEmail';
        }
        if ($values['message'] === '') {
            $errors['message'] = 'required';
        } elseif ($len($values['message']) < 10) {
            $errors['message'] = 'tooShort';
        } elseif ($len($values['message']) > 5000) {
            $errors['message'] = 'required';
        }
        if (!isset($purposes[$values['purpose']])) {
            $values['purpose'] = (string) array_key_first($purposes);
        }
        if ($values['church'] !== '' && !Site::church($values['church'])) {
            $values['church'] = '';
        }
        if (empty($purposes[$values['purpose']]['is_prayer'])) {
            $values['confidential'] = false;
        }

        if ($errors) {
            self::respond(['status' => 'invalid', 'errors' => $errors, 'values' => $values], 422);
        }

        // A valid form from a bot: hidden field filled, or faster than a person types.
        $age = self::stampAge($text('startedAt'));
        $minMs = (int) Site::setting('contact.min_seconds', '3') * 1000;
        if ($text('website') !== '' || $age === null || $age < $minMs) {
            self::respond(['status' => 'sent']);
        }
        if (!self::allow()) {
            self::respond(['status' => 'error', 'values' => $values], 429);
        }

        $church = $values['church'] !== '' ? $values['church'] : null;
        $subject = Site::t('format.contactSubject', [
            'confidential' => $values['confidential'] ? Site::t('format.contactConfidential') : '',
            'purpose' => $values['purpose'],
            'church' => $church ? Site::t('format.contactChurch', ['church' => $church]) : '',
            'name' => $values['name'],
        ], Site::defaultLocale());
        $subject = trim((string) preg_replace('/\s+/', ' ', $subject));
        $body = Site::t('format.contactBody', [
            'message' => $values['message'],
            'name' => $values['name'],
            'email' => $values['email'],
            'church' => $church ? Site::t('format.contactBodyChurch', ['church' => $church], Site::defaultLocale()) : '',
        ], Site::defaultLocale());
        $pastor = Site::setting('mail.to_pastor');
        $to = ($values['confidential'] && $pastor !== '') ? $pastor : Site::setting('mail.to', Site::setting('org.email'));

        if (!Mailer::configured()) {
            self::respond([
                'status' => 'fallback',
                'values' => $values,
                'mailto' => 'mailto:' . $to . '?subject=' . \Adepc\Schedule::encodeURIComponent($subject) . '&body=' . \Adepc\Schedule::encodeURIComponent($body),
            ]);
        }
        try {
            Mailer::send($to, $subject, $body, $values['email']);
            self::respond(['status' => 'sent']);
        } catch (\Throwable $e) {
            error_log('[contact] ' . $e->getMessage());
            self::respond(['status' => 'error', 'values' => $values], 502);
        }
    }

    /** At most 5 messages per address per 10 minutes. */
    private static function allow(): bool
    {
        $key = 'contact_' . substr(hash('sha256', Http::ip() . self::key()), 0, 16);
        $file = Cache::dir() . '/' . $key . '.json';
        $now = time();
        $hits = is_file($file) ? array_filter((array) json_decode((string) file_get_contents($file), true), static fn ($t) => $t > $now - 600) : [];
        if (count($hits) >= 5) {
            return false;
        }
        $hits[] = $now;
        @file_put_contents($file, json_encode(array_values($hits)), LOCK_EX);
        return true;
    }

    private static function respond(array $state, int $status = 200): never
    {
        if (Http::wantsJson()) {
            $state['message'] = match ($state['status']) {
                'sent' => Site::t('contact.form.success'),
                'fallback' => Site::t('contact.form.fallback', ['email' => Site::setting('org.email')]),
                'error' => Site::t('contact.form.error', ['email' => Site::setting('org.email')]),
                default => '',
            };
            foreach ($state['errors'] ?? [] as $field => $code) {
                $state['errorText'][$field] = Site::t('contact.form.' . $code);
            }
            unset($state['values']);
            Http::json($state, $status === 422 ? 422 : 200);
        }
        if ($state['status'] === 'sent') {
            Http::redirect(Site::url('contact', null, [], ['sent' => '1']), 303);
        }
        Pages::contact($state, $status === 422 ? 422 : 200);
        exit;
    }
}
