#!/usr/bin/env python3
"""
For every class used by the PHP site's pages, compares the CSS rules that
target it in public/assets/css/site.css with the Next.js build's stylesheet.
Rules are keyed by (at-rule context, selector) and compared declaration by
declaration after light normalisation (whitespace, zero units, quotes).

  python3 tools/compare/css_diff.py [--php http://localhost:8080]
"""
import argparse
import glob
import re
import sys
import urllib.request
from html.parser import HTMLParser

ROUTES = ["/fr", "/en", "/fr/eglises", "/en/churches/ottawa", "/fr/evenements", "/en/watch", "/fr/a-propos",
          "/en/stories", "/fr/donner", "/en/contact", "/fr/confidentialite", "/en/nope"]


class Classes(HTMLParser):
    def __init__(self):
        super().__init__()
        self.found = set()

    def handle_starttag(self, tag, attrs):
        for k, v in attrs:
            if k == "class" and v:
                self.found.update(v.split())


def rules(css):
    """Flattens CSS into {(context, selector): {prop: value}}."""
    out = {}

    def walk(text, ctx):
        i = 0
        while i < len(text):
            brace = text.find("{", i)
            if brace == -1:
                break
            head = text[i:brace].strip().lstrip(";").strip()
            depth, j = 1, brace + 1
            while j < len(text) and depth:
                depth += {"{": 1, "}": -1}.get(text[j], 0)
                j += 1
            body = text[brace + 1:j - 1]
            if head.startswith("@media") or head.startswith("@supports") or head.startswith("@layer") or head.startswith("@container"):
                walk(body, ctx + (re.sub(r"\s+", " ", head) if not head.startswith("@layer") else "",))
            elif not head.startswith("@"):
                decls = {}
                for d in re.split(r";(?![^(]*\))", body):
                    if ":" in d and "{" not in d:
                        p, v = d.split(":", 1)
                        decls[p.strip()] = norm(v)
                for sel in split_selectors(head):
                    out.setdefault((tuple(c for c in ctx if c), sel), {}).update(decls)
            i = j
    walk(css, ())
    return out


def split_selectors(head):
    parts, depth, cur = [], 0, ""
    for ch in head:
        if ch in "([":
            depth += 1
        elif ch in ")]":
            depth -= 1
        if ch == "," and depth == 0:
            parts.append(cur.strip())
            cur = ""
        else:
            cur += ch
    parts.append(cur.strip())
    return [re.sub(r"\s+", " ", p) for p in parts if p]


def norm(v):
    v = v.strip().replace("!important", " !important")
    v = re.sub(r"\s+", " ", v)
    v = re.sub(r"\b0(px|rem|em|%)\b", "0", v)
    v = v.replace('"', "'")
    return v


def unescape_class(sel_class):
    return re.sub(r"\\(.)", r"\1", sel_class)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--php", default="http://localhost:8080")
    a = ap.parse_args()
    used = set()
    for r in ROUTES:
        try:
            html = urllib.request.urlopen(a.php + r).read().decode()
        except urllib.error.HTTPError as e:
            html = e.read().decode()
        c = Classes()
        c.feed(html)
        used |= c.found
    php = rules(open("public/assets/css/site.css").read())
    next_css = [f for f in glob.glob("../adepc/.next/static/chunks/*.css") if "--font-instrument-sans" in open(f).read() or ".edge{" in open(f).read()]
    ref = rules("".join(open(f).read() for f in next_css))

    def targets(rmap, cls):
        out = {}
        for (ctx, sel), decls in rmap.items():
            for m in re.finditer(r"\.((?:\\.|[A-Za-z0-9_-])+)", sel):
                if unescape_class(m.group(1)) == cls:
                    out[(ctx, sel)] = decls
                    break
        return out

    diffs = 0
    for cls in sorted(used):
        p, r = targets(php, cls), targets(ref, cls)
        for key in sorted(set(p) | set(r)):
            if p.get(key) != r.get(key):
                diffs += 1
                if diffs <= 40:
                    print(f".{cls}  {key[0]} {key[1]}\n   php: {p.get(key)}\n   ref: {r.get(key)}")
    print(f"{len(used)} classes used; {diffs} differing rules")
    sys.exit(1 if diffs else 0)


if __name__ == "__main__":
    main()
