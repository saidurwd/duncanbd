# Content migration from the legacy duncanbd.com

Two steps: **crawl** the old site into a local manifest, then **import** it as draft CMS content.
Nothing is published automatically. Redirects from old URLs are intentionally not generated (not required).

## 1. Crawl

```sh
python3 tools/legacy/crawl.py                           # what an anonymous visitor can see
python3 tools/legacy/crawl.py --cookie 'DUNCANBD=…'     # a logged-in session you are authorised to use
```

Output goes to `storage/app/legacy/` (`manifest.json` and `files/`). It is git-ignored: the content is the
company's own and should not be committed. The crawler is polite (0.4 s between requests), never logs in or guesses
credentials, and records anything it cannot read under `skipped` in the manifest.

## 2. Import

```sh
php artisan legacy:import                # or --path=/some/other/folder
```

Creates, all as **drafts**:

| Source | Becomes |
|---|---|
| Navigation tree | Pages with the same hierarchy, plus the Explore menu (two levels) |
| Readable `/content/N.html` pages | A rich-text block (cleaned: inline styles, `<input>` buttons, `&nbsp;` spacers removed) plus a gallery of the page's images |
| Unreadable pages | A placeholder page `[CONTENT TO MIGRATE …]` so the structure is ready; listed at the end of the run |
| Gallery albums | Media folders, media items (title and alt text taken from the descriptive filenames, to be reviewed), and gallery pages |
| PDFs linked from the home page | Documents (Annual reports category), unpublished |
| Contact page | Dhaka address and phone in Settings (only where empty), parent group = Camellia Plc |
| Home page | A starter home page (hero from the gallery, intro text, latest reports) |

The import is idempotent: re-running skips anything that already exists, so it is safe to crawl again with a
session cookie once more pages become readable (delete the placeholder page first to re-import it).

## After importing

1. Review every page in the admin and replace any `[CONTENT TO MIGRATE]` placeholder.
2. Improve image alt text (the importer reuses the filename, e.g. "Tapping (female worker)").
3. Confirm figures with Finance, then publish page by page (Pages → In review → Approved → Published).
