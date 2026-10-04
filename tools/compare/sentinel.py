#!/usr/bin/env python3
"""Crawls the PHP site after sentinel.sql: every visible word must carry "§"."""
import re
import sys
import urllib.request
from html.parser import HTMLParser

BASE = sys.argv[1] if len(sys.argv) > 1 else "http://localhost:8080"
ROUTES = ["/fr", "/en", "/fr/eglises", "/en/churches", "/fr/eglises/montreal", "/en/churches/ottawa", "/fr/evenements", "/en/events",
          "/fr/regarder", "/en/watch", "/fr/a-propos", "/en/about", "/fr/histoires", "/en/stories", "/fr/donner", "/en/give",
          "/fr/contact", "/en/contact?purpose=visit&church=granby", "/fr/confidentialite", "/en/privacy", "/fr/nope", "/nope.png"]
ATTRS = {"alt", "aria-label", "title", "placeholder", "data-word"}
META = re.compile(r"^(description|og:(title|description|site_name|image:alt)|twitter:(title|description|image:alt)|application-name)$")


class Collect(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.out, self.skip = [], 0

    def handle_starttag(self, tag, attrs):
        if tag in ("script", "style"):
            self.skip += 1
        a = dict(attrs)
        for k in ATTRS & a.keys():
            self.out.append((f"<{tag} {k}>", a[k] or ""))
        if tag == "meta" and META.match(a.get("name") or a.get("property") or ""):
            self.out.append((f"<meta {a.get('name') or a.get('property')}>", a.get("content") or ""))

    def handle_endtag(self, tag):
        if tag in ("script", "style"):
            self.skip -= 1

    def handle_data(self, data):
        if not self.skip:
            self.out.append(("text", data))


bad = 0
for route in ROUTES:
    try:
        html = urllib.request.urlopen(BASE + route).read().decode()
    except urllib.error.HTTPError as e:
        html = e.read().decode()
    c = Collect()
    c.feed(html)
    problems = [(w, v.strip()) for w, v in c.out if re.search(r"[^\W\d_]", v) and "§" not in v]
    print(f"{route:42} {'OK' if not problems else str(len(problems)) + ' strings not from the database'}")
    for w, v in problems[:10]:
        print("     ", w, repr(v[:80]))
    bad += len(problems)
sys.exit(1 if bad else 0)
