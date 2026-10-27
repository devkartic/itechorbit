#!/usr/bin/env python3
"""Read public sitemaps and audit server-rendered pages; emit JSON to stdout.

Usage: python3 dev/audit-public-pages.py http://itechorbit.local
Requests update normal server access logs and public article view counters.
"""
import collections
import concurrent.futures
import html.parser
import json
import sys
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET


class Page(html.parser.HTMLParser):
    def __init__(self):
        super().__init__()
        self.title = ''
        self.h1 = []
        self.description = ''
        self.robots = ''
        self.canonical = ''
        self.images_without_alt = 0
        self.images_without_dimensions = 0
        self.schemas = []
        self.links = []
        self._title = False
        self._h1 = False
        self._json = False
        self._schema = ''

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'title':
            self._title = True
        if tag == 'h1':
            self._h1 = True
            self.h1.append('')
        if tag == 'meta':
            if attrs.get('name') == 'description':
                self.description = attrs.get('content', '')
            if attrs.get('name') == 'robots':
                self.robots = attrs.get('content', '')
        if tag == 'link' and attrs.get('rel') == 'canonical':
            self.canonical = attrs.get('href', '')
        if tag == 'a' and attrs.get('href'):
            self.links.append(attrs['href'])
        if tag == 'img':
            self.images_without_alt += int('alt' not in attrs)
            self.images_without_dimensions += int(not attrs.get('width') or not attrs.get('height'))
        if tag == 'script' and attrs.get('type') == 'application/ld+json':
            self._json = True
            self._schema = ''

    def handle_data(self, data):
        if self._title:
            self.title += data
        if self._h1:
            self.h1[-1] += data
        if self._json:
            self._schema += data

    def handle_endtag(self, tag):
        if tag == 'title':
            self._title = False
        if tag == 'h1':
            self._h1 = False
        if tag == 'script' and self._json:
            try:
                self.schemas.append(json.loads(self._schema))
            except json.JSONDecodeError:
                self.schemas.append({'error': 'invalid JSON-LD'})
            self._json = False


def audit(base):
    base = base.rstrip('/')

    def get(path):
        url = base + path
        request = urllib.request.Request(url, headers={'User-Agent': 'iTechOrbitPublicAudit/1.0', 'Accept': 'text/html,application/xml'})
        try:
            with urllib.request.urlopen(request, timeout=25) as response:
                return response.status, response.url, response.read().decode('utf-8', 'replace')
        except urllib.error.HTTPError as error:
            return error.code, url, error.read().decode('utf-8', 'replace')

    def local_path(url):
        parsed = urllib.parse.urlsplit(url)
        return parsed.path + ('?' + parsed.query if parsed.query else '')

    paths = set()
    pending = ['/sitemap.xml']
    seen = set()
    while pending:
        path = pending.pop()
        if path in seen:
            continue
        seen.add(path)
        status, _, body = get(path)
        if status != 200:
            raise RuntimeError(f'Sitemap failed: {path}: {status}')
        root = ET.fromstring(body)
        locations = [local_path(node.text) for node in root.findall('.//{*}loc')]
        if root.tag.endswith('sitemapindex'):
            pending.extend(locations)
        else:
            paths.update(locations)
    sitemap_paths = sorted(paths)
    paths.update(['/login', '/register', '/password/reset', '/blogs/search', '/blogs?key=audit', '/work?category=audit', '/not-a-real-page', '/portfolios', '/about', '/contact', '/blog'])
    paths.update(path.replace('/products/', '/services/', 1) for path in sitemap_paths if path.startswith('/products/'))
    paths.update(path + '/contact' for path in sitemap_paths if path.startswith('/portfolios/') and path.count('/') == 2)

    def inspect(path):
        try:
            status, final, body = get(path)
            page = Page()
            page.feed(body)
            issues = []
            expected_alias = {'/about': '/about/info', '/contact': '/contact/info', '/blog': '/blogs'}.get(path)
            if expected_alias and urllib.parse.urlsplit(final).path != expected_alias:
                issues.append('Legacy alias does not redirect to ' + expected_alias)
            if status == 200:
                if len(page.h1) != 1:
                    issues.append(f'Expected one SSR h1; found {len(page.h1)}')
                if not page.title:
                    issues.append('Missing SSR title')
                if path in sitemap_paths:
                    if not page.description:
                        issues.append('Missing SSR description')
                    if not page.canonical:
                        issues.append('Missing SSR canonical')
                    if 'noindex' in page.robots:
                        issues.append('Sitemap includes a noindex page')
                if page.images_without_alt:
                    issues.append(f'{page.images_without_alt} images missing alt attributes')
                if page.images_without_dimensions:
                    issues.append(f'{page.images_without_dimensions} images missing dimensions')
                if any('error' in schema for schema in page.schemas if isinstance(schema, dict)):
                    issues.append('Invalid JSON-LD')
            elif path in sitemap_paths:
                issues.append(f'Sitemap URL returned {status}')
            return {'path': path, 'status': status, 'final_url': final, 'in_sitemap': path in sitemap_paths, 'title': page.title, 'h1': page.h1, 'description': page.description, 'canonical': page.canonical, 'robots': page.robots, 'schema_types': [schema.get('@type', '@graph') for schema in page.schemas if isinstance(schema, dict)], 'internal_links': sorted({local_path(link) for link in page.links if link.startswith('/') and not link.startswith('//')}), 'issues': issues}
        except (OSError, ValueError) as error:
            return {'path': path, 'issues': [type(error).__name__]}

    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as executor:
        records = list(executor.map(inspect, sorted(paths)))
    known = {record['path']: record for record in records}
    links = {link for record in records for link in record.get('internal_links', [])}
    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as executor:
        linked_records = list(executor.map(inspect, sorted(links - known.keys())))
    broken_links = [{'path': record['path'], 'status': record.get('status'), 'issues': record['issues']} for record in [*records, *linked_records] if record['path'] in links and record.get('status') != 200]
    titles = collections.defaultdict(list)
    for record in records:
        if record.get('in_sitemap') and record.get('title'):
            titles[record['title']].append(record['path'])
    return {'base_url': base, 'sitemap_urls': len(sitemap_paths), 'checked_urls': len(records) + len(linked_records), 'linked_urls_checked': len(links), 'broken_internal_links': broken_links, 'duplicate_indexable_titles': {key: value for key, value in titles.items() if len(value) > 1}, 'pages': sorted([*records, *linked_records], key=lambda record: record['path'])}


if __name__ == '__main__':
    if len(sys.argv) != 2 or urllib.parse.urlsplit(sys.argv[1]).scheme not in ('http', 'https'):
        sys.exit('Usage: python3 dev/audit-public-pages.py http(s)://host[:port]')
    print(json.dumps(audit(sys.argv[1]), indent=2, ensure_ascii=False))
