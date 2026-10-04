<?php
declare(strict_types=1);

namespace Adepc;

use DateTimeImmutable;
use DateTimeZone;

/** Service times and dates. Logic only; every word comes from translations. */
final class Schedule
{
    public static function tz(): DateTimeZone
    {
        return new DateTimeZone(Site::setting('schedule.timezone', 'UTC'));
    }

    /** The coming Sunday in the church's time zone (today if it is Sunday). */
    public static function nextSunday(?DateTimeImmutable $now = null): array
    {
        $today = ($now ?? new DateTimeImmutable('now'))->setTimezone(self::tz())->setTime(12, 0);
        $weekday = (int) $today->format('w');
        $offset = $weekday === 0 ? 0 : 7 - $weekday;
        return ['date' => $today->modify("+$offset days"), 'isToday' => $offset === 0];
    }

    public static function dayDate(DateTimeImmutable $date, ?string $locale = null): string
    {
        return Site::t('date.dayDate', [
            'weekday' => Site::t('date.weekday.' . $date->format('w'), [], $locale),
            'day' => $date->format('j'),
            'month' => Site::t('date.month.' . $date->format('n'), [], $locale),
        ], $locale);
    }

    /** "13 h 30" in French, "1:30" + "PM" in English, per the translation patterns. */
    public static function timeParts(string $time, ?string $locale = null): array
    {
        [$h, $m] = array_map('intval', explode(':', $time) + [1 => 0]);
        $vars = ['h' => $h, 'h12' => ($h % 12) ?: 12, 'mm' => str_pad((string) $m, 2, '0', STR_PAD_LEFT)];
        return [
            'main' => Site::t($m ? 'date.timeWithMinutes' : 'date.timeOnHour', $vars, $locale),
            'suffix' => Site::t($h < 12 ? 'date.am' : 'date.pm', [], $locale),
        ];
    }

    public static function formatTime(string $time, ?string $locale = null): string
    {
        $p = self::timeParts($time, $locale);
        return $p['suffix'] !== '' ? Site::t('format.timeWithSuffix', ['time' => $p['main'], 'suffix' => $p['suffix']], $locale) : $p['main'];
    }

    public static function serviceOn(array $church, string $day): ?array
    {
        foreach ($church['services'] as $s) {
            if ($s['day'] === $day) {
                return $s;
            }
        }
        return null;
    }

    /** Churches ordered by the time their Sunday service starts (stable). */
    public static function bySundayTime(array $churches): array
    {
        $keyed = [];
        foreach (array_values($churches) as $i => $c) {
            $keyed[] = [self::serviceOn($c, 'sunday')['time'] ?? '99', $i, $c];
        }
        usort($keyed, static fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
        return array_column($keyed, 2);
    }

    /** Big event date: day, short month, year. */
    public static function eventDate(string $iso, ?string $locale = null): array
    {
        $d = new DateTimeImmutable($iso . ' 12:00:00', self::tz());
        return [
            'day' => $d->format('j'),
            'month' => Site::t('date.monthShort.' . $d->format('n'), [], $locale),
            'year' => $d->format('Y'),
        ];
    }

    public static function directionsUrl(array $church): string
    {
        $destination = Site::t('format.directionsAddress', [
            'street' => $church['street'],
            'locality' => $church['locality'],
            'region' => $church['region'],
            'postal' => $church['postal_code'],
        ]);
        return str_replace('{destination}', self::encodeURIComponent($destination), Site::setting('maps.directions_url'));
    }

    /** JavaScript's encodeURIComponent, so links match the original byte for byte. */
    public static function encodeURIComponent(string $s): string
    {
        return strtr(rawurlencode($s), ['%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')']);
    }

    public static function telHref(string $phone): string
    {
        return 'tel:+' . Site::setting('phone.country_code') . preg_replace('/\D/', '', $phone);
    }
}
