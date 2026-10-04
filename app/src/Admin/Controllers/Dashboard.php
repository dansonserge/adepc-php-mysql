<?php
declare(strict_types=1);

namespace Adepc\Admin\Controllers;

use Adepc\Admin\A;
use Adepc\Admin\Schema;
use Adepc\Db;

/** Default values still in place, facts that stay hidden until filled, editorial notes. */
final class Dashboard
{
    public static function show(): void
    {
        $defaults = [];
        $groups = Schema::settings();
        foreach (Db::all('SELECT skey FROM settings WHERE is_default = 1 ORDER BY skey') as $r) {
            $group = 'organization';
            $label = preg_replace('/\.(fr|en)$/', '', $r['skey']);
            foreach ($groups as $g => $def) {
                foreach ($def['fields'] as $f) {
                    if ($f[0] === $r['skey'] || $f[0] . '.fr' === $r['skey'] || $f[0] . '.en' === $r['skey']) {
                        $group = $g;
                        $label = A::t($f['label']);
                    }
                }
            }
            $defaults[] = ['label' => A::t('admin.dash.setting', ['key' => $label]), 'link' => '/admin/settings/' . $group];
        }
        $defaults = array_values(array_unique($defaults, SORT_REGULAR));
        foreach (Db::all('SELECT id, name FROM churches WHERE pastor_is_default = 1 ORDER BY sort') as $r) {
            $defaults[] = ['label' => A::t('admin.dash.churchPastor', ['church' => $r['name']]), 'link' => '/admin/churches/' . $r['id']];
        }
        foreach (Db::all('SELECT id, title_fr, date_is_default, time_is_default, venue_is_default FROM events WHERE date_is_default = 1 OR time_is_default = 1 OR venue_is_default = 1') as $r) {
            foreach (['date' => 'admin.f.date', 'time' => 'admin.f.time', 'venue' => 'admin.f.venue'] as $k => $label) {
                if ($r[$k . '_is_default']) {
                    $defaults[] = ['label' => A::t('admin.dash.eventField', ['event' => $r['title_fr'], 'field' => A::t($label)]), 'link' => '/admin/events/' . $r['id']];
                }
            }
        }

        $hidden = [];
        $setting = static fn ($k) => (string) Db::value('SELECT value FROM settings WHERE skey = ?', [$k]);
        if ($setting('org.phone') === '') {
            $hidden[] = ['label' => A::t('admin.dash.phone'), 'link' => '/admin/settings/organization'];
        }
        if (!Db::value("SELECT COUNT(*) FROM list_items WHERE list_key = 'social' AND visible = 1")) {
            $hidden[] = ['label' => A::t('admin.dash.social'), 'link' => '/admin/pages/common'];
        }
        if ($setting('youtube.channel_id') === '') {
            $hidden[] = ['label' => A::t('admin.dash.youtube'), 'link' => '/admin/settings/integrations'];
        }
        foreach (Db::all("SELECT id, title_fr FROM events WHERE kind = 'featured' AND (registration_url IS NULL OR registration_url = '')") as $r) {
            $hidden[] = ['label' => A::t('admin.dash.registration', ['event' => $r['title_fr']]), 'link' => '/admin/events/' . $r['id']];
        }
        if ($setting('mail.transport') === 'none') {
            $hidden[] = ['label' => A::t('admin.dash.mail'), 'link' => '/admin/settings/email'];
        }
        if ($setting('maps.mapbox_token') === '') {
            $hidden[] = ['label' => A::t('admin.dash.mapbox'), 'link' => '/admin/settings/integrations'];
        }

        A::render('dashboard', [
            'title' => A::t('admin.nav.dashboard'),
            'section' => 'dashboard',
            'defaults' => $defaults,
            'hidden' => $hidden,
            'confirm' => Db::all("SELECT id, name, confirm_note FROM stories WHERE confirm_note IS NOT NULL AND confirm_note <> ''"),
            'notes' => Db::all('SELECT * FROM admin_notes ORDER BY kind DESC, sort'),
        ]);
    }
}
