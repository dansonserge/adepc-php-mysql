<?php
declare(strict_types=1);

namespace Adepc\Admin\Controllers;

use Adepc\Admin\A;
use Adepc\Admin\Form;
use Adepc\Admin\Router;
use Adepc\Admin\Schema;
use Adepc\Db;
use Adepc\Http;
use Adepc\Site;

/** Create, edit, reorder and delete for every resource declared in Schema::resources(). */
final class Resources
{
    public static function handle(string $key, ?string $id, ?string $action): void
    {
        $res = Schema::resources()[$key];
        if ($id === null) {
            self::index($key, $res);
            return;
        }
        if ($id === 'new') {
            Http::isPost() ? self::save($key, $res, null) : self::edit($key, $res, null, [], []);
            return;
        }
        $row = Db::one("SELECT * FROM `{$res['table']}` WHERE id = ?", [(int) $id]);
        if (!$row) {
            Router::notFound();
            return;
        }
        if (Http::isPost()) {
            match ($action) {
                'delete' => self::delete($key, $res, $row),
                'up', 'down' => self::move($key, $res, $row, $action),
                default => self::save($key, $res, $row),
            };
            return;
        }
        self::edit($key, $res, $row, [], []);
    }

    private static function index(string $key, array $res): void
    {
        $order = $res['sortable'] ? 'sort, id' : 'id';
        A::render('resource-list', [
            'title' => A::t($res['label']),
            'section' => $key,
            'key' => $key,
            'res' => $res,
            'rows' => Db::all("SELECT * FROM `{$res['table']}` ORDER BY $order"),
        ]);
    }

    private static function edit(string $key, array $res, ?array $row, array $errors, array $posted): void
    {
        $values = $row ?? self::defaults($res);
        if ($row && $key === 'events') {
            $values['churches'] = array_map('intval', array_column(Db::all('SELECT church_id FROM event_churches WHERE event_id = ? ORDER BY sort', [$row['id']]), 'church_id'));
        }
        if ($row && $key === 'churches') {
            $values['services'] = Db::all('SELECT * FROM church_services WHERE church_id = ? ORDER BY sort, id', [$row['id']]);
        }
        $values = array_merge($values, $posted);
        A::render('resource-edit', [
            'title' => $row ? A::t('admin.action.editThing', ['thing' => A::t($res['singular'])]) : A::t('admin.action.newThing', ['thing' => A::t($res['singular'])]),
            'section' => $key,
            'key' => $key,
            'res' => $res,
            'row' => $row,
            'values' => $values,
            'errors' => $errors,
        ], $errors ? 422 : 200);
    }

    private static function defaults(array $res): array
    {
        $d = ['published' => 1, 'visible' => 1, 'status' => '308', 'source_kind' => 'file', 'kind' => 'recurring', 'date_mode' => 'none', 'time_mode' => 'none'];
        if ($res['table'] === 'churches') {
            $d['services'] = [['day' => 'sunday', 'time' => '', 'label_fr' => '', 'label_en' => '']];
        }
        return $d;
    }

    private static function save(string $key, array $res, ?array $row): void
    {
        $in = (array) ($_POST['f'] ?? []);
        $errors = [];
        $data = [];
        $relations = [];
        foreach ($res['fields'] as $f) {
            if ($f[1] === 'services') {
                continue;
            }
            $values = Form::read($f, $in, $errors);
            if ($f[1] === 'churches') {
                $relations[$f[0]] = $values[$f[0]];
                continue;
            }
            $data += $values;
        }
        $services = $key === 'churches' ? self::readServices($errors) : [];

        // Rules that span fields.
        foreach (['slug', 'pkey', 'from_path'] as $unique) {
            if (isset($data[$unique]) && $data[$unique] !== null) {
                $taken = Db::value("SELECT id FROM `{$res['table']}` WHERE `$unique` = ? AND id <> ?", [$data[$unique], $row['id'] ?? 0]);
                if ($taken) {
                    $errors[$unique] = 'admin.v.unique';
                }
            }
        }
        if ($key === 'redirects') {
            foreach (['from_path', 'to_path'] as $p) {
                if (!empty($data[$p]) && !str_starts_with($data[$p], '/')) {
                    $errors[$p] = 'admin.v.path';
                }
            }
        }
        if ($key === 'stories' && !empty($data['published']) && empty($data['consent'])) {
            $errors['consent'] = 'admin.v.consent';
        }
        if ($key === 'events') {
            if ($data['date_mode'] === 'date' && empty($data['event_date'])) {
                $errors['event_date'] = 'admin.v.required';
            }
            if ($data['time_mode'] === 'time' && empty($data['event_time'])) {
                $errors['event_time'] = 'admin.v.required';
            }
        }
        if ($key === 'videos') {
            if ($data['source_kind'] === 'youtube' && empty($data['youtube_id'])) {
                $errors['youtube_id'] = 'admin.v.required';
            }
            if ($data['source_kind'] === 'file' && empty($data['mp4_media_id'])) {
                $errors['mp4_media_id'] = 'admin.v.required';
            }
        }
        if ($key === 'categories' && ($data['role'] ?? '') === '') {
            $data['role'] = null;
        }

        if ($errors) {
            A::flash('error', 'admin.flash.fixErrors');
            $posted = $data + $relations;
            if ($key === 'churches') {
                $posted['services'] = $services;
            }
            self::edit($key, $res, $row, $errors, $posted);
            return;
        }

        // A field saved with a new value is no longer a seeded default.
        foreach ($res['fields'] as $f) {
            if (isset($f['default_flag']) && $row && !empty($row[$f['default_flag']])) {
                foreach (Form::columns($f) as $col) {
                    if (array_key_exists($col, $data) && (string) $data[$col] !== (string) $row[$col]) {
                        $data[$f['default_flag']] = 0;
                    }
                }
            }
        }
        if (array_key_exists('updated_at', $row ?? self::columns($res['table']))) {
            $data['updated_at'] = A::now();
        }

        $id = Db::transaction(function () use ($key, $res, $row, $data, $relations, $services): int {
            if ($row) {
                Db::update($res['table'], $data, 'id = ?', [$row['id']]);
                $id = (int) $row['id'];
            } else {
                if ($res['sortable']) {
                    $data['sort'] = (int) Db::value("SELECT COALESCE(MAX(sort), 0) + 1 FROM `{$res['table']}`");
                }
                $id = Db::insert($res['table'], $data);
            }
            if ($key === 'events') {
                Db::exec('DELETE FROM event_churches WHERE event_id = ?', [$id]);
                foreach ($data['all_churches'] ? [] : $relations['churches'] as $i => $cid) {
                    Db::insert('event_churches', ['event_id' => $id, 'church_id' => $cid, 'sort' => $i + 1]);
                }
            }
            if ($key === 'churches') {
                Db::exec('DELETE FROM church_services WHERE church_id = ?', [$id]);
                foreach ($services as $i => $s) {
                    Db::insert('church_services', ['church_id' => $id] + $s + ['sort' => $i + 1]);
                }
                if ($row && $row['slug'] !== $data['slug']) {
                    self::redirectChurchSlug($row['slug'], $data['slug']);
                }
            }
            return $id;
        });

        A::changed();
        A::flash('ok', 'admin.flash.saved');
        A::redirect('/admin/' . $key . '/' . $id);
    }

    private static function columns(string $table): array
    {
        $cols = [];
        foreach (Db::all("SHOW COLUMNS FROM `$table`") as $c) {
            $cols[$c['Field']] = true;
        }
        return $cols;
    }

    /** @return list<array{day: string, time: string, label_fr: string, label_en: string}> */
    private static function readServices(array &$errors): array
    {
        $out = [];
        foreach ((array) ($_POST['services'] ?? []) as $s) {
            if (!is_array($s) || !empty($s['remove'])) {
                continue;
            }
            $time = trim((string) ($s['time'] ?? ''));
            $day = in_array($s['day'] ?? '', ['sunday', 'friday'], true) ? $s['day'] : 'sunday';
            if ($time === '' && trim((string) ($s['label_fr'] ?? '')) === '') {
                continue;
            }
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
                $errors['services'] = 'admin.v.time';
            }
            $out[] = ['day' => $day, 'time' => $time, 'label_fr' => trim((string) ($s['label_fr'] ?? '')), 'label_en' => trim((string) ($s['label_en'] ?? ''))];
        }
        return $out;
    }

    /** Old church URLs keep working after a slug change. */
    private static function redirectChurchSlug(string $old, string $new): void
    {
        foreach (Site::locales() as $code => $l) {
            $from = Site::url('church', $code, ['slug' => $old]);
            $to = Site::url('church', $code, ['slug' => $new]);
            Db::exec('DELETE FROM redirects WHERE from_path = ?', [$from]);
            Db::exec('UPDATE redirects SET to_path = ? WHERE to_path = ?', [$to, $from]);
            Db::insert('redirects', ['from_path' => $from, 'to_path' => $to, 'status' => 308, 'is_auto' => 1]);
        }
    }

    private static function move(string $key, array $res, array $row, string $dir): void
    {
        if (!$res['sortable']) {
            A::redirect('/admin/' . $key);
        }
        $rows = Db::all("SELECT id FROM `{$res['table']}` ORDER BY sort, id");
        $ids = array_map('intval', array_column($rows, 'id'));
        $i = array_search((int) $row['id'], $ids, true);
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if ($i !== false && isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            foreach ($ids as $pos => $rid) {
                Db::exec("UPDATE `{$res['table']}` SET sort = ? WHERE id = ?", [$pos + 1, $rid]);
            }
            A::changed();
        }
        A::redirect('/admin/' . $key);
    }

    private static function delete(string $key, array $res, array $row): void
    {
        if ($key === 'categories' && Db::value('SELECT COUNT(*) FROM videos WHERE category_id = ?', [$row['id']])) {
            A::flash('error', 'admin.flash.categoryInUse');
            A::redirect('/admin/categories');
        }
        Db::transaction(function () use ($key, $res, $row): void {
            $id = (int) $row['id'];
            if ($key === 'churches') {
                Db::exec('DELETE FROM church_services WHERE church_id = ?', [$id]);
                Db::exec('DELETE FROM event_churches WHERE church_id = ?', [$id]);
                foreach (['media', 'videos', 'stories'] as $t) {
                    Db::exec("UPDATE `$t` SET church_id = NULL WHERE church_id = ?", [$id]);
                }
            }
            if ($key === 'events') {
                Db::exec('DELETE FROM event_churches WHERE event_id = ?', [$id]);
            }
            Db::exec("DELETE FROM `{$res['table']}` WHERE id = ?", [$id]);
        });
        A::changed();
        A::flash('ok', 'admin.flash.deleted');
        A::redirect('/admin/' . $key);
    }
}
