# Going live with the old website's content

The old duncanbd.com was crawled on 17 Oct 2026 (all 27 content pages, the 6 gallery albums, 39 files). The result is loaded by one seeder:

```sh
python3 tools/legacy/crawl.py                              # re-read the old site (output: storage/app/legacy, git-ignored)
php artisan db:seed --class=LegacyContentSeeder            # build the new site from it
```

On a server, run the crawl there (or copy `storage/app/legacy/` across) before seeding. The seeder **replaces all content** (never users); on production it refuses to run over existing content unless `LEGACY_RESET=1`.

## What the new site contains (all from the old site, nothing invented)

| Area | Source |
|---|---|
| Home | The old home text word for word, hero from the photo gallery, three figures taken from the inner pages (1859; ≈18,500 people; 12–13 million kg of tea a year), product cards, Annual Report 2025, AGM news item |
| **About Duncan**: About, History of Duncan, Camellia Plc, Lawrie Group | Pages 2–5 (the History page also carries a 22-entry timeline of key dates taken from its text) |
| **Products**: Tea (+ Tea production, Tea gardens), Rubber (+ production, gardens), Natural mineral water | Pages 6–13; the 16 gardens become estate records with their own pages; 13 are marked as also growing rubber |
| **Corporate Responsibility**: Primary Health Care, CDF Hospital, CDF School, CDF Orchid Project, Yasser Life Guard, Environment, Other Social Commitments | Pages 14–21, each illustrated with the matching photographs |
| **Associate companies**: Duncan Products, CWL, Octavius, Duncan Properties, UIC, UFL | Pages 22–28 |
| Gallery | The 5 old albums (Tea, Rubber, DPL/Orchid, Hospital/School, Award) with descriptive alt text |
| Investors / news | Annual Report 2025 (PDF) and its AGM news item. The old site publishes nothing else |
| Settings | Dhaka address, telephone and fax from the Contact page; parent group Camellia Plc |
| Menu | Same four sections as the old site, plus Gallery, News, Contact |

Pages and documents are created **published**, because the content is already public on the old site. The four legal pages (Privacy notice, Cookie policy, Terms of use, Accessibility statement) are **drafts** with a placeholder: the old site has none, and they must be written by the Company Secretary / Legal before launch.

## Left out on purpose (the old site has no data)

People (board, leadership), job vacancies, a general e-mail address and enquiry routing inboxes, social-media links, policies. Add them in the admin; until the enquiry inboxes are set (Settings → Enquiry routing) enquiries go to the mail "from" address.

## Contradictions inside the old site: decide before launch

1. **Number of tea estates.** The home page says *14 tea estates* and *two rubber processing plants*; the Products, Tea and Health pages say *16 gardens*, *18,500 hectares*, rubber processed at *a central factory at Lungla*, and a *60-teacher* school system across 16 gardens. The new home keeps the old home text unchanged, so it says 14 while the Tea pages say 16. Confirm the current figures with Finance and edit the home text and the Tea business record (Admin → Pages / Company content).
2. **Dates and figures are as of about 2016** on most inner pages (e.g. "last 25 years" for UIC, UFL "as of 31 December 2016", Duncan Brothers "150 years ago"). Update before launch or add an "as at" note.
3. **Duncan Products telephone**: the Natural Mineral Water page gives +88 02 41062016 / 41062011 and the Associate page gives 9669251. Both are kept as written; confirm which is current.
4. **Spelling** errors from the old text (e.g. "Chittagong" vs "Chattogram", "subsidiray", "Octavious") were not corrected; proof-read in the admin.

## Checklist before publishing

- [ ] Resolve the contradictions above and proof-read every page (they are plain editable pages)
- [ ] Write the four legal pages and publish them; add them to Menus → Legal links
- [ ] Add people, vacancies, enquiry inboxes, social links as available
- [ ] Review image alt text (descriptive text is supplied for all 38 photographs)
- [ ] Clear the page cache after seeding in any environment that already served pages: `php artisan responsecache:clear`
