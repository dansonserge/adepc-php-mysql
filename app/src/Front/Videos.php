<?php
declare(strict_types=1);

namespace Adepc\Front;

use Adepc\Cache;
use Adepc\Site;

/**
 * Videos for the site: the YouTube channel's latest uploads (public Atom feed,
 * cached one hour, no API key) followed by the videos managed in the admin.
 */
final class Videos
{
    private static ?array $all = null;

    /** @return list<array> normalized videos */
    public static function all(): array
    {
        if (self::$all !== null) {
            return self::$all;
        }
        $categories = [];
        foreach (Site::d()['categories'] as $c) {
            $categories[(int) $c['id']] = $c;
        }
        $out = self::youtube($categories);
        foreach (Site::d()['videos'] as $v) {
            $cat = $categories[(int) $v['category_id']] ?? null;
            if (!$cat) {
                continue;
            }
            $mp4 = Site::media($v['mp4_media_id']);
            $webm = Site::media($v['webm_media_id']);
            $source = $v['source_kind'] === 'youtube'
                ? ['kind' => 'youtube', 'id' => (string) $v['youtube_id']]
                : ['kind' => 'file', 'mp4' => Site::mediaUrl($mp4), 'webm' => $webm ? Site::mediaUrl($webm) : null];
            if ($source['kind'] === 'file' && $source['mp4'] === '') {
                continue;
            }
            $out[] = [
                'key' => 'v-' . $v['id'],
                'id' => (int) $v['id'],
                'title_fr' => $v['title_fr'],
                'title_en' => $v['title_en'],
                'category' => $cat,
                'date_label' => $v['date_label'],
                'duration_seconds' => $v['duration_seconds'] !== null ? (int) $v['duration_seconds'] : null,
                'poster' => Site::media($v['poster_media_id']),
                'poster_url' => null,
                'source' => $source,
            ];
        }
        return self::$all = $out;
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $v) {
            if (($v['id'] ?? null) === $id) {
                return $v;
            }
        }
        return null;
    }

    public static function inRole(string $role): array
    {
        return array_values(array_filter(self::all(), static fn ($v) => ($v['category']['role'] ?? null) === $role));
    }

    private static function youtube(array $categories): array
    {
        $channel = Site::setting('youtube.channel_id');
        if ($channel === '') {
            return [];
        }
        $byRole = [];
        foreach ($categories as $c) {
            if ($c['role']) {
                $byRole[$c['role']] = $c;
            }
        }
        if (!isset($byRole['messages'])) {
            return [];
        }
        $entries = Cache::remember('youtube_' . md5($channel), 3600, static function () use ($channel): array {
            $xml = self::fetch(str_replace('{channel}', rawurlencode($channel), Site::setting('youtube.feed_url')));
            if ($xml === null) {
                return [];
            }
            $found = [];
            foreach (array_slice(explode('<entry>', $xml), 1) as $entry) {
                preg_match('#<yt:videoId>([^<]+)</yt:videoId>#', $entry, $id);
                preg_match('#<title>([^<]*)</title>#', $entry, $title);
                preg_match('#<published>([^<]+)</published>#', $entry, $published);
                if (!$id || !$title) {
                    continue;
                }
                $found[] = [
                    'id' => $id[1],
                    'title' => html_entity_decode($title[1], ENT_QUOTES | ENT_XML1, 'UTF-8'),
                    'date' => isset($published[1]) ? substr($published[1], 0, 10) : null,
                ];
            }
            return $found;
        });
        $pattern = Site::setting('youtube.live_pattern');
        $out = [];
        foreach (array_slice($entries, 0, max(1, (int) Site::setting('youtube.limit', '12'))) as $e) {
            $isLive = $pattern !== '' && isset($byRole['live']) && preg_match('/\b(' . str_replace('/', '\/', $pattern) . ')\b/iu', $e['title']);
            $out[] = [
                'key' => 'yt-' . $e['id'],
                'id' => null,
                'title_fr' => $e['title'],
                'title_en' => $e['title'],
                'category' => $isLive ? $byRole['live'] : $byRole['messages'],
                'date_label' => $e['date'],
                'duration_seconds' => null,
                'poster' => null,
                'poster_url' => str_replace('{id}', rawurlencode($e['id']), Site::setting('youtube.thumb_url')),
                'source' => ['kind' => 'youtube', 'id' => $e['id']],
            ];
        }
        return $out;
    }

    private static function fetch(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6, CURLOPT_FOLLOWLOCATION => true]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            return is_string($body) && $code === 200 ? $body : null;
        }
        $ctx = stream_context_create(['http' => ['timeout' => 6]]);
        $body = @file_get_contents($url, false, $ctx);
        return is_string($body) ? $body : null;
    }
}
