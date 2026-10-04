<?php
declare(strict_types=1);

namespace Adepc\Admin;

use Adepc\Db;

/** Where a media item is used, so it can't be deleted while the site needs it. */
final class Usage
{
    /** @return list<array{label: string, link: string}> */
    public static function of(int $id): array
    {
        $out = [];
        foreach (Db::all('SELECT skey FROM media_slots WHERE media_id = ?', [$id]) as $r) {
            $out[] = ['label' => A::t(Schema::slots()[$r['skey']][1] ?? 'admin.nav.pages') . ' (' . $r['skey'] . ')', 'link' => '/admin/pages'];
        }
        foreach (Db::all('SELECT id, name FROM churches WHERE hero_media_id = ?', [$id]) as $r) {
            $out[] = ['label' => $r['name'], 'link' => '/admin/churches/' . $r['id']];
        }
        foreach (Db::all('SELECT id, title_fr FROM events WHERE media_id = ?', [$id]) as $r) {
            $out[] = ['label' => $r['title_fr'], 'link' => '/admin/events/' . $r['id']];
        }
        foreach (Db::all('SELECT id, title_fr FROM videos WHERE ? IN (poster_media_id, mp4_media_id, webm_media_id)', [$id]) as $r) {
            $out[] = ['label' => $r['title_fr'], 'link' => '/admin/videos/' . $r['id']];
        }
        foreach (Db::all('SELECT id, name FROM stories WHERE media_id = ?', [$id]) as $r) {
            $out[] = ['label' => $r['name'], 'link' => '/admin/stories/' . $r['id']];
        }
        foreach (Db::all('SELECT list_key, title_fr FROM list_items WHERE media_id = ?', [$id]) as $r) {
            $out[] = ['label' => (string) $r['title_fr'] . ' (' . $r['list_key'] . ')', 'link' => '/admin/pages'];
        }
        return $out;
    }
}
