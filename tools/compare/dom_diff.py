#!/usr/bin/env python3
"""
Golden-master DOM comparison: the Next.js site (reference) vs the PHP site.

Compares the <body> of every route element by element: tag names, attributes,
class lists and text. Normalizes only what is expected to differ by design:
framework scripts, image file URLs (Next's optimizer vs pre-generated WebP),
the blur placeholder's tiny JPEG, server-action form plumbing, and the
PHP site's data-js-* hooks.

  python3 tools/compare/dom_diff.py [--ref http://localhost:3000] [--php http://localhost:8080] [/fr /en/about ...]
"""
import argparse
import re
import sys
import urllib.request
from html.parser import HTMLParser

ROUTES = [
    "/fr", "/en",
    "/fr/eglises", "/en/churches",
    "/fr/eglises/montreal", "/en/churches/montreal", "/fr/eglises/granby", "/en/churches/granby",
    "/fr/eglises/ottawa", "/en/churches/ottawa", "/fr/eglises/quebec", "/en/churches/quebec",
    "/fr/evenements", "/en/events",
    "/fr/regarder", "/en/watch",
    "/fr/a-propos", "/en/about",
    "/fr/histoires", "/en/stories",
    "/fr/donner", "/en/give",
    "/fr/contact", "/en/contact",
    "/fr/confidentialite", "/en/privacy",
    "/en/does-not-exist",
]

VOID = {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "source", "track", "wbr"}
SKIP = {"script", "style", "template", "noscript"}


class Node:
    def __init__(self, tag, attrs, parent=None):
        self.tag, self.attrs, self.parent, self.children = tag, attrs, parent, []


class Tree(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.root = Node("#root", {})
        self.cur = self.root
        self.skip = 0

    def handle_starttag(self, tag, attrs):
        if self.skip or tag in SKIP:
            if tag in SKIP:
                self.skip += 1
            return
        node = Node(tag, {k: (v if v is not None else "") for k, v in attrs}, self.cur)
        self.cur.children.append(node)
        if tag not in VOID:
            self.cur = node

    def handle_startendtag(self, tag, attrs):
        if self.skip or tag in SKIP:
            return
        self.cur.children.append(Node(tag, {k: (v if v is not None else "") for k, v in attrs}, self.cur))

    def handle_endtag(self, tag):
        if tag in SKIP:
            self.skip = max(0, self.skip - 1)
            return
        if self.skip or tag in VOID:
            return
        n = self.cur
        while n is not self.root and n.tag != tag:
            n = n.parent
        if n is not self.root:
            self.cur = n.parent

    def handle_data(self, data):
        if self.skip:
            return
        if self.cur.children and isinstance(self.cur.children[-1], str):
            self.cur.children[-1] += data
        else:
            self.cur.children.append(data)


def fetch(url):
    req = urllib.request.Request(url, headers={"Accept-Language": "fr"})
    try:
        with urllib.request.urlopen(req) as r:
            return r.read().decode("utf-8")
    except urllib.error.HTTPError as e:
        return e.read().decode("utf-8")


def body(html):
    t = Tree()
    t.feed(html)
    stack = [t.root]
    while stack:
        n = stack.pop()
        if isinstance(n, Node):
            if n.tag == "body":
                return n
            stack.extend(n.children)
    return t.root


BLUR = re.compile(r"background-image:url\(\"data:image/svg\+xml[^)]*\)\)?\"?\)?")


def norm_attrs(node):
    a = dict(node.attrs)
    for k in list(a):
        if k.startswith("data-js") or k in ("srcset", "imagesrcset"):
            del a[k]
    if node.tag in ("img", "source", "video") and "src" in a:
        del a["src"]
    if "class" in a:
        a["class"] = " ".join(a["class"].split())
    if "style" in a:
        s = re.sub(r"background-image:url\(.*$", "background-image:BLUR", a["style"])
        s = s.replace('background-image:url("/media/icons/chevron.svg")', "background-image:CHEVRON")
        s = re.sub(r'background-image:url\("data:image/svg\+xml,%3Csvg[^"]*viewBox=\'0 0 12 8\'[^"]*"\)', "background-image:CHEVRON", s)
        a["style"] = s.rstrip(";")
    if node.tag == "form":
        for k in ("action", "method", "enctype"):
            a.pop(k, None)
    if node.tag == "input" and a.get("name") == "startedAt":
        a.pop("value", None)
    if a.get("id", "").startswith("paypal-"):
        a["id"] = "paypal-*"
    return a


def children(node):
    out = []
    for c in node.children:
        if isinstance(c, str):
            if c.strip() == "" and (not out or not isinstance(out[-1], str)):
                # whitespace-only text between elements carries no meaning
                continue
            out.append(c)
        else:
            if c.tag == "input" and c.attrs.get("type") == "hidden" and c.attrs.get("name", "").startswith("$ACTION"):
                continue
            if c.tag == "div" and c.attrs.get("hidden") is not None and not c.children:
                continue
            if c.tag == "a" and "data-js-mailto" in c.attrs:
                continue
            out.append(c)
    # React splits text with comments: merge adjacent strings
    merged = []
    for c in out:
        if isinstance(c, str) and merged and isinstance(merged[-1], str):
            merged[-1] += c
        else:
            merged.append(c)
    return merged


def path_of(node):
    parts = []
    while node is not None and node.tag not in ("#root", "body"):
        label = node.tag
        if node.attrs.get("id"):
            label += "#" + node.attrs["id"]
        elif node.attrs.get("class"):
            label += "." + ".".join(node.attrs["class"].split()[:2])
        parts.append(label)
        node = node.parent
    return " > ".join(reversed(parts[-6:]))


def compare(a, b, diffs):
    if isinstance(a, str) or isinstance(b, str):
        if a != b:
            diffs.append(("text", repr(a)[:120], repr(b)[:120]))
        return
    if a.tag != b.tag:
        diffs.append(("tag", path_of(a), f"{a.tag} vs {b.tag}"))
        return
    aa, ba = norm_attrs(a), norm_attrs(b)
    if aa != ba:
        keys = sorted(set(aa) | set(ba))
        changed = {k: (aa.get(k), ba.get(k)) for k in keys if aa.get(k) != ba.get(k)}
        diffs.append(("attrs", path_of(a), changed))
    ac, bc = children(a), children(b)
    if len(ac) != len(bc):
        diffs.append(("children", path_of(a), f"{len(ac)} vs {len(bc)}: "
                      + " | ".join(x if isinstance(x, str) else x.tag for x in ac)[:200] + "  <>  "
                      + " | ".join(x if isinstance(x, str) else x.tag for x in bc)[:200]))
    for x, y in zip(ac, bc):
        compare(x, y, diffs)


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--ref", default="http://localhost:3000")
    p.add_argument("--php", default="http://localhost:8080")
    p.add_argument("--max", type=int, default=12)
    p.add_argument("routes", nargs="*")
    args = p.parse_args()
    total = 0
    for route in args.routes or ROUTES:
        ref = body(fetch(args.ref + route))
        php = body(fetch(args.php + route))
        diffs = []
        compare(ref, php, diffs)
        total += len(diffs)
        print(f"{route:28} {'OK' if not diffs else str(len(diffs)) + ' differences'}")
        for d in diffs[: args.max]:
            print("   ", d)
    sys.exit(1 if total else 0)


if __name__ == "__main__":
    main()
