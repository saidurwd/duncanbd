#!/usr/bin/env python3
"""
Crawl the legacy duncanbd.com site into a local manifest + asset folder that
`php artisan legacy:import` turns into CMS content.

Only reads what an anonymous visitor (or the supplied session cookie) can see; it never logs in or guesses
credentials. Pages that redirect to the login screen are recorded as `login_required` so nothing is silently lost.

    python3 tools/legacy/crawl.py                          # public content only
    python3 tools/legacy/crawl.py --cookie 'DUNCANBD=...'  # a session you are authorised to use

Output (default storage/app/legacy/): manifest.json and files/. Content is the company's own; keep it out of git.
"""
import argparse
import html
import http.cookiejar
import json
import os
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser

BASE = 'https://duncanbd.com'
UA = 'Mozilla/5.0 (compatible; DuncanBD-content-migration/1.0)'


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


class Client:
    def __init__(self, cookie=None, delay=0.4):
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar), NoRedirect)
        self.cookie = cookie
        self.delay = delay

    def get(self, url, binary=False):
        """Returns (status, location, body). Redirects are reported, not followed."""
        time.sleep(self.delay)
        req = urllib.request.Request(urllib.parse.urljoin(BASE, url), headers={'User-Agent': UA})
        if self.cookie:
            req.add_header('Cookie', self.cookie)
        try:
            with self.opener.open(req, timeout=60) as r:
                body = r.read()
                return r.status, None, body if binary else body.decode('utf-8', 'replace')
        except urllib.error.HTTPError as e:
            return e.code, e.headers.get('Location'), b'' if binary else ''


def balanced_div(source, start_marker):
    """Inner HTML of the <div> that starts at start_marker, honouring nested divs."""
    i = source.find(start_marker)
    if i < 0:
        return None
    j = source.find('>', i) + 1
    depth, pos = 1, j
    for m in re.finditer(r'<(/?)div\b[^>]*>', source[j:]):
        depth += -1 if m.group(1) else 1
        if depth == 0:
            return source[j:j + m.start()].strip()
    return source[j:].strip()


class Text(HTMLParser):
    def __init__(self):
        super().__init__()
        self.parts = []

    def handle_data(self, d):
        self.parts.append(d)


def text_of(fragment):
    p = Text()
    p.feed(fragment)
    return re.sub(r'\s+', ' ', html.unescape(''.join(p.parts))).strip()


def parse_nav(home):
    """Groups (collapsible headings) with their items; 'submenue-left' items are children of the previous item."""
    nav = balanced_div(home, '<nav') or home
    groups = []
    for m in re.finditer(r'<li><a href="#"[^>]*>([^<]+)</a>\s*<ul[^>]*>(.*?)</ul>', nav, re.S):
        items, parent = [], None
        for a in re.finditer(r'<a class="([^"]*)" href="([^"]+)">([^<]+)</a>', m.group(2)):
            cls, href, title = a.group(1), a.group(2), re.sub(r'\s+', ' ', html.unescape(a.group(3))).strip()
            item = {'title': title, 'href': href, 'parent': parent if 'submenue-left' in cls else None}
            if 'submenue-left' not in cls:
                parent = len(items)
            items.append(item)
        groups.append({'title': html.unescape(m.group(1)).strip(), 'items': items})
    return groups


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--out', default='storage/app/legacy')
    ap.add_argument('--cookie', help='Cookie header for a session you are authorised to use')
    ap.add_argument('--delay', type=float, default=0.4, help='seconds between requests')
    args = ap.parse_args()

    client = Client(args.cookie, args.delay)
    files_dir = os.path.join(args.out, 'files')
    os.makedirs(files_dir, exist_ok=True)

    status, _, home = client.get('/index.html')
    if status != 200:
        sys.exit(f'Home page returned {status}')

    manifest = {'source': BASE, 'crawled_at': time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime()), 'pages': [], 'gallery': [], 'documents': [], 'contact': None, 'home': None, 'skipped': []}
    assets = {}   # url -> local filename

    def want_asset(url):
        url = urllib.parse.urljoin(BASE + '/', html.unescape(url))
        if not urllib.parse.urlparse(url).path.startswith('/uploads/'):
            return None
        name = urllib.parse.unquote(os.path.basename(urllib.parse.urlparse(url).path))
        assets[url] = name
        return name

    # --- home -----------------------------------------------------------------------------------
    main_html = balanced_div(home, '<div class="col-md-7">') or ''
    docs = []
    for a in re.finditer(r'href="([^"]+\.pdf)"', main_html, re.I):
        name = want_asset(a.group(1))
        if name:
            docs.append(name)
    intro = re.search(r'<h4>(At Duncan.*?)</h4>', main_html, re.S)
    news_titles = [text_of(t) for t in re.findall(r'<h4>(.*?)</h4>', main_html, re.S) if text_of(t) and 'About us' not in t and not t.lstrip().startswith('<a')]
    manifest['home'] = {
        'intro': text_of(intro.group(1)) if intro else None,
        'news': [{'title': t, 'files': docs} for t in news_titles if not t.startswith('At Duncan')],
        'keywords': (re.search(r'name="keywords" content="([^"]*)"', home) or [None, None])[1],
    }
    for name in dict.fromkeys(docs):
        manifest['documents'].append({'file': name, 'title': os.path.splitext(name)[0].replace('_', ' '), 'source': 'home'})

    # --- navigation + content pages ---------------------------------------------------------------
    groups = parse_nav(home)
    manifest['nav'] = groups
    for group in groups:
        for item in group['items']:
            href = item['href']
            if not re.match(r'^/content/\d+\.html$', href):
                continue
            status, location, body = client.get(href)
            page = {'href': href, 'title': item['title'], 'group': group['title'], 'parent_title': group['items'][item['parent']]['title'] if item['parent'] is not None else None}
            if status == 200:
                content = balanced_div(body, '<div class="col-md-7">') or ''
                page['status'] = 'ok'
                page['body_html'] = content
                page['images'] = [n for n in (want_asset(u) for u in re.findall(r'<img[^>]+src="([^"]+)"', content)) if n]
                page['files'] = [n for n in (want_asset(u) for u in re.findall(r'href="([^"]+\.(?:pdf|docx?|xlsx?))"', content, re.I)) if n]
            elif status in (301, 302, 303) and location and 'login' in location:
                page['status'] = 'login_required'
                manifest['skipped'].append({'url': href, 'reason': 'redirects to login for this session'})
            else:
                page['status'] = f'http_{status}'
                manifest['skipped'].append({'url': href, 'reason': f'HTTP {status}'})
            manifest['pages'].append(page)

    # --- gallery ---------------------------------------------------------------------------------
    # Only the page's own content column: the surrounding template also lists every site banner.
    status, _, gindex = client.get('/gallery/index.html')
    if status == 200:
        gmain = balanced_div(gindex, '<div class="col-md-7">') or ''
        seen = set()
        for m in re.finditer(r'href="(/gallery/category/(\d+)\.html)"[^>]*>\s*([^<\[]+?)\s*(?:\[(\d+)\])?\s*<', gmain):
            href, label = m.group(1), html.unescape(m.group(3)).strip()
            if href in seen:
                continue
            seen.add(href)
            status, _, body = client.get(href)
            images = []
            if status == 200:
                content = balanced_div(body, '<div class="col-md-7">') or ''
                for u in dict.fromkeys(re.findall(r'href="(/uploads/[^"]+\.(?:jpe?g|png|webp))"', content, re.I)):
                    name = want_asset(u)
                    if name:
                        images.append(name)
            manifest['gallery'].append({'category': label, 'source': href, 'images': images})

    # --- contact ---------------------------------------------------------------------------------
    status, _, contact = client.get('/contact.html')
    if status == 200:
        block = balanced_div(contact, '<div class="col-md-7">') or ''
        manifest['contact'] = {'text': re.sub(r'\s*\n\s*', '\n', re.sub(r'<br\s*/?>', '\n', re.sub(r'</(p|div|h\d)>', '\n', block)))}
        manifest['contact']['text'] = html.unescape(re.sub(r'<[^>]+>', '', manifest['contact']['text'])).strip()

    # --- download assets ---------------------------------------------------------------------------
    for url, name in assets.items():
        dest = os.path.join(files_dir, name)
        if os.path.exists(dest):
            continue
        status, _, data = client.get(url, binary=True)
        if status == 200 and data:
            with open(dest, 'wb') as f:
                f.write(data)
            print(f'  downloaded {name} ({len(data) // 1024} KB)')
        else:
            manifest['skipped'].append({'url': url, 'reason': f'asset HTTP {status}'})

    with open(os.path.join(args.out, 'manifest.json'), 'w', encoding='utf-8') as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)

    ok = sum(1 for p in manifest['pages'] if p['status'] == 'ok')
    print(f"Pages: {ok} readable, {len(manifest['pages']) - ok} not readable. Gallery categories: {len(manifest['gallery'])}. Assets: {len(assets)}.")
    if manifest['skipped']:
        print(f"Skipped {len(manifest['skipped'])} item(s); see manifest.json > skipped.")


if __name__ == '__main__':
    main()
