<?php
declare(strict_types=1);

/*
 * Template helpers. They produce the exact markup the React components did,
 * but every word and image they print is passed in from the database.
 */

use Adepc\Img;
use Adepc\Site;

function e(mixed $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Renders HTML attributes; null values are skipped, true renders as an empty value. */
function attrs(array $a): string
{
    $out = '';
    foreach ($a as $k => $v) {
        if ($v === null || $v === false) {
            continue;
        }
        $out .= ' ' . $k . '="' . ($v === true ? '' : e($v)) . '"';
    }
    return $out;
}

function t(string $key, array $vars = []): string
{
    return Site::t($key, $vars);
}

function L(array $row, string $field): string
{
    return Site::L($row, $field);
}

function setting(string $key, string $default = ''): string
{
    return Site::setting($key, $default);
}

function settingL(string $key): string
{
    return Site::settingL($key);
}

function url(string $page, array $params = [], array $query = [], string $hash = ''): string
{
    return Site::url($page, null, $params, $query, $hash);
}

/** Keeps compound names like "Henri-Bourassa" on one line (non-breaking hyphen). */
function nb(string $text): string
{
    return str_replace('-', "\u{2011}", $text);
}

function pad2(int $n): string
{
    return str_pad((string) $n, 2, '0', STR_PAD_LEFT);
}

/** Length in UTF-16 code units, like JavaScript's String#length. */
function js_length(string $s): int
{
    return intdiv(strlen(mb_convert_encoding($s, 'UTF-16LE', 'UTF-8')), 2);
}

/** Inlines an icon file from public/media/icons, adding aria-hidden and the class. */
function icon(string $key, string $class): string
{
    static $files = [];
    if (!isset($files[$key])) {
        $path = PUBLIC_DIR . '/media/icons/' . basename($key) . '.svg';
        $files[$key] = is_file($path) ? trim((string) file_get_contents($path)) : '';
    }
    if ($files[$key] === '') {
        return '';
    }
    return preg_replace('/^<svg\b/', '<svg aria-hidden="true" class="' . e($class) . '"', $files[$key], 1) ?? '';
}

function arrow(string $class = ''): string
{
    return icon('arrow', 'h-3 w-5 shrink-0 transition-transform duration-300 ease-expo group-hover:translate-x-1 ' . $class);
}

/** Rectangular, uppercase, 56px button; a fill wipes in from the left on hover. */
function button_class(string $variant = 'gold', string $extra = ''): string
{
    $variants = [
        'gold' => 'bg-gold text-ink before:bg-white',
        'ink' => 'bg-ink text-white before:bg-blue',
        'light' => 'bg-white text-ink before:bg-gold',
        'outline-light' => 'border border-white/70 text-white before:bg-white hover:text-ink',
        'outline-dark' => 'border border-ink text-ink before:bg-ink hover:text-white',
    ];
    return implode(' ', [
        'group relative isolate inline-flex min-h-14 items-center justify-center gap-3 overflow-hidden px-7',
        'text-nav font-semibold uppercase whitespace-nowrap transition-colors duration-300',
        'before:absolute before:inset-0 before:-z-10 before:origin-left before:scale-x-0 before:transition-transform before:duration-300 before:ease-expo',
        'hover:before:scale-x-100',
        $variants[$variant],
        $extra,
    ]);
}

function button_link(string $href, string $label, string $variant = 'gold', string $class = '', bool $arrow = true): string
{
    return '<a href="' . e($href) . '" class="' . e(button_class($variant, $class)) . '"><span>' . e($label) . '</span>'
        . ($arrow ? arrow() : '') . '</a>';
}

function external_button(string $href, string $label, string $variant = 'gold', string $class = ''): string
{
    return '<a href="' . e($href) . '" class="' . e(button_class($variant, $class)) . '" target="_blank" rel="noopener noreferrer"><span>'
        . e($label) . '</span>' . arrow() . '</a>';
}

/** Text link with an arrow; http(s) links open in a new tab. */
function arrow_link(string $href, string $label, string $class = ''): string
{
    $cls = 'group inline-flex items-center gap-3 text-nav font-semibold uppercase underline-offset-[6px] hover:underline ' . $class;
    $web = str_starts_with($href, 'http');
    return '<a href="' . e($href) . '" class="' . e($cls) . '"' . ($web ? ' target="_blank" rel="noopener noreferrer"' : '') . '><span>'
        . e($label) . '</span>' . arrow() . '</a>';
}

/** Section label: optional index, a short rule, then the label (already escaped HTML). */
function eyebrow(string $html, string $class = '', string $tag = 'p', ?string $index = null): string
{
    return '<' . $tag . ' class="caption flex items-center gap-3 ' . e($class) . '">'
        . ($index !== null ? '<span class="tabular-nums">' . e($index) . '</span>' : '')
        . '<span aria-hidden="true" class="h-px w-8 bg-current opacity-60"></span><span>' . $html . '</span></' . $tag . '>';
}

/** Stacked display words scaled so the longest line spans the column (FitText). */
function fit_text(array $lines, string $tag = 'p', string $class = '', string $max = '18rem', float $k = 0.5, bool $rise = false, ?string $id = null): string
{
    $chars = max(array_map('js_length', $lines ?: ['']));
    $style = '--chars:' . $chars . ';--k:' . $k . ';--fit-max:' . $max;
    $html = '<' . $tag . attrs(['id' => $id, 'class' => 'display fit ' . ($rise ? 'rise' : '') . ' ' . $class, 'style' => $style]) . '>';
    foreach ($lines as $line) {
        $html .= $rise
            ? '<span class="rise-line whitespace-nowrap"><span>' . e($line) . '</span></span>'
            : '<span class="block whitespace-nowrap">' . e($line) . '</span>';
    }
    return $html . '</' . $tag . '>';
}

/**
 * A photograph filling its container (the Photo component). Options: sizes, class,
 * priority, reveal (default true), decorative, blend ('paper'|'cream'), focus.
 */
function photo(?array $media, array $o): string
{
    if (!$media) {
        return '';
    }
    $class = (string) ($o['class'] ?? '');
    $priority = (bool) ($o['priority'] ?? false);
    $reveal = (bool) ($o['reveal'] ?? true);
    $blend = $o['blend'] ?? false;
    $position = preg_match('/\b(absolute|fixed)\b/', $class) ? '' : 'relative';
    $bg = $blend === 'paper' ? 'bg-paper' : ($blend === 'cream' ? 'bg-cream' : 'bg-ink/10');
    $img = Img::tag($media, [
        'alt' => ($o['decorative'] ?? false) ? '' : Site::L($media, 'alt'),
        'fill' => true,
        'sizes' => $o['sizes'] ?? null,
        'preload' => $priority,
        'blur' => true,
        'class' => 'object-cover ' . ($blend ? 'mix-blend-multiply' : ''),
        'position' => $o['focus'] ?? ($media['focus'] ?: '50% 50%'),
        'attrs' => $reveal && !$priority ? ['data-reveal-media' => ''] : [],
    ]);
    return '<div' . attrs([
        'class' => $position . ' overflow-hidden ' . $bg . ' ' . $class,
        'data-reveal' => $reveal && !$priority ? 'clip' : null,
    ]) . '>' . $img . '</div>';
}

/** Paragraphs from a multi-line text field (blank line = new paragraph). */
function paragraphs(string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', $text) ?: []), 'strlen'));
}

function partial(string $name, array $vars = []): string
{
    return \Adepc\View::render('site/' . $name, $vars);
}

/** Cache-busting URL for a file in public/. */
function asset(string $path): string
{
    $file = PUBLIC_DIR . $path;
    return $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

/** React never emits whitespace between tags; templates are formatted, so collapse it. */
function compact_html(string $html): string
{
    return (string) preg_replace('/>\s+</', '><', $html);
}

/** URL/ID-safe slug: "Worship" → "worship", "Église" → "eglise". */
function slugify(string $s): string
{
    $s = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s)));
    return trim($s, '-');
}
