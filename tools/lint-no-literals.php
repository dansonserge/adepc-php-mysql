<?php
declare(strict_types=1);

/*
 * Fails when any visible text is written in code instead of coming from the
 * database or a media file:
 *   - text nodes in the literal HTML of templates,
 *   - literal alt / aria-label / title / placeholder / content attributes,
 *   - text inside HTML fragments built in PHP strings (helpers, views),
 *   - text assigned in the public site's JavaScript.
 *
 *   php tools/lint-no-literals.php
 */
$root = dirname(__DIR__);
$problems = [];

$phpFiles = array_merge(
    glob($root . '/app/views/site/*.php'),
    glob($root . '/app/views/site/*/*.php'),
    glob($root . '/app/views/site/*/*/*.php'),
    glob($root . '/app/views/admin/*.php'),
    glob($root . '/app/views/admin/*/*.php'),
    [$root . '/app/src/helpers.php', $root . '/app/src/Img.php', $root . '/app/src/Admin/Form.php'],
    glob($root . '/app/src/Front/*.php'),
);

const MARK = "\u{E000}"; // stands for anything PHP prints

/** Text nodes and checked attribute values of an HTML string (PHP output replaced by MARK). */
$scan = static function (string $html): array {
    $found = [];
    $html = preg_replace('#<(script|style)\b.*?</\1>#is', '', $html) ?? $html;
    $html = preg_replace('#<!--.*?-->#s', '', $html) ?? $html;
    $len = strlen($html);
    $i = 0;
    while ($i < $len) {
        $lt = strpos($html, '<', $i);
        $text = $lt === false ? substr($html, $i) : substr($html, $i, $lt - $i);
        foreach (explode(MARK, html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')) as $piece) {
            if (preg_match('/\p{L}/u', $piece)) {
                $found[] = 'text "' . trim($piece) . '"';
            }
        }
        if ($lt === false) {
            break;
        }
        // Read the tag, respecting quoted attribute values.
        $j = $lt + 1;
        $quote = null;
        while ($j < $len) {
            $c = $html[$j];
            if ($quote) {
                if ($c === $quote) {
                    $quote = null;
                }
            } elseif ($c === '"' || $c === "'") {
                $quote = $c;
            } elseif ($c === '>') {
                break;
            }
            $j++;
        }
        $tag = substr($html, $lt, $j - $lt + 1);
        if (preg_match_all('/\b(alt|aria-label|title|placeholder|data-word)="([^"]*)"/u', $tag, $m, PREG_SET_ORDER)) {
            foreach ($m as [$all, $name, $value]) {
                foreach (explode(MARK, $value) as $piece) {
                    if (preg_match('/\p{L}/u', $piece)) {
                        $found[] = "attribute $all";
                        break;
                    }
                }
            }
        }
        $i = $j + 1;
    }
    return $found;
};

foreach ($phpFiles as $file) {
    $rel = substr($file, strlen($root) + 1);
    $html = '';
    foreach (token_get_all((string) file_get_contents($file)) as $tok) {
        $id = is_array($tok) ? $tok[0] : null;
        $src = is_array($tok) ? $tok[1] : $tok;
        if ($id === T_INLINE_HTML) {
            $html .= $src;
        } elseif ($id === T_OPEN_TAG_WITH_ECHO) {
            $html .= MARK;
        }
        if ($id === T_CONSTANT_ENCAPSED_STRING && str_contains($src, '<')) {
            $str = substr($src, 1, -1);
            if ($src[0] === '"') {
                $str = stripcslashes($str);
            }
            if (in_array($str[0] ?? '', ['/', '#'], true)) {
                continue; // a regular expression, not markup
            }
            if ($str[0] !== '<') {
                // Starts inside a tag begun in another string: skip to the tag's end.
                $close = strpos($str, '>');
                $str = $close === false ? '' : substr($str, $close + 1);
            }
            foreach ($scan($str) as $p) {
                $problems[] = "$rel:{$tok[2]} in a PHP string: $p";
            }
        }
    }
    foreach ($scan($html) as $p) {
        $problems[] = "$rel: $p";
    }
}

foreach (array_merge(glob($root . '/public/assets/js/site/*.js'), [$root . '/public/assets/js/admin.js']) as $file) {
    $rel = substr($file, strlen($root) + 1);
    foreach (file($file) as $n => $line) {
        if (preg_match('/(textContent|innerText|title|alt|placeholder)\s*[:=]\s*[\'"`][^\'"`]*\p{L}/u', $line)
            || preg_match('/setAttribute\(\s*[\'"](aria-label|title|alt|placeholder)[\'"]\s*,\s*[\'"`][^\'"`]*\p{L}/u', $line)) {
            $problems[] = "$rel:" . ($n + 1) . ' ' . trim($line);
        }
    }
}

if ($problems) {
    echo "Visible text written in code:\n  " . implode("\n  ", $problems) . "\n";
    exit(1);
}
echo "no literal text in templates, helpers or scripts\n";
