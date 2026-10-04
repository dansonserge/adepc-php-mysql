<?php
declare(strict_types=1);

namespace Adepc;

use DOMDocument;
use DOMElement;

/**
 * Icons may be replaced from the admin. An uploaded SVG is rebuilt from an
 * allowlist of shape elements and presentation attributes: no scripts, no
 * event handlers, no links, no external references.
 */
final class SvgSanitizer
{
    private const ELEMENTS = ['svg', 'g', 'path', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'rect'];
    private const ATTRIBUTES = [
        'viewbox', 'xmlns', 'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'points', 'width', 'height',
        'fill', 'fill-rule', 'clip-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit',
        'stroke-dasharray', 'stroke-dashoffset', 'opacity', 'fill-opacity', 'stroke-opacity', 'transform',
    ];

    public static function clean(string $svg): ?string
    {
        if (stripos($svg, '<!ENTITY') !== false || stripos($svg, '<!DOCTYPE') !== false) {
            return null;
        }
        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $doc->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $root = $doc->documentElement;
        if (!$ok || !$root || strtolower($root->localName) !== 'svg') {
            return null;
        }
        $out = new DOMDocument();
        $copy = self::copy($root, $out);
        if (!$copy) {
            return null;
        }
        $out->appendChild($copy);
        return trim((string) $out->saveXML($out->documentElement));
    }

    private static function copy(DOMElement $el, DOMDocument $out): ?DOMElement
    {
        $name = strtolower($el->localName);
        if (!in_array($name, self::ELEMENTS, true)) {
            return null;
        }
        $node = $out->createElement($name === 'svg' ? 'svg' : $name);
        foreach ($el->attributes as $attr) {
            $a = strtolower($attr->localName);
            $v = $attr->value;
            if (!in_array($a, self::ATTRIBUTES, true) || preg_match('/url\s*\(|javascript:|data:|expression/i', $v)) {
                continue;
            }
            $node->setAttribute($a === 'viewbox' ? 'viewBox' : $a, $v);
        }
        foreach ($el->childNodes as $child) {
            if ($child instanceof DOMElement && ($c = self::copy($child, $out))) {
                $node->appendChild($c);
            }
        }
        return $node;
    }
}
