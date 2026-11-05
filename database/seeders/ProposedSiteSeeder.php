<?php

namespace Database\Seeders;

use App\Domain\Business\Models\Business;
use App\Domain\Cms\Actions\SaveMenu;
use App\Domain\Cms\Actions\SavePage;
use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\Enums\PageStatus;
use App\Domain\Cms\Events\PageContentChanged;
use App\Domain\Cms\Models\Page;
use App\Domain\Cms\Models\Redirect;
use App\Domain\Identity\Models\User;
use App\Domain\Media\Models\MediaItem;
use App\Domain\People\Models\Person;
use App\Domain\Settings\SettingsRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * Builds the site structure proposed in "Duncan Brothers - proposed website structure and content Oct 2026"
 * (docs/laurav1): Home, About (Our Story, Leadership), Operations (Tea, Rubber), ESG, News and Contact.
 *
 * Non-destructive: it creates or updates only the pages listed below (matched by path), replaces the header
 * and footer menus and sets the tagline. Existing pages, news, documents and media are left alone, so the old
 * pages stay reachable by URL. Re-running it is safe.
 *
 * Copy is taken from the document as supplied. Items the document marks as open questions (purpose statement,
 * values, ESG detail, recent history, buyer terms, procurement contacts, photographs) are deliberately NOT
 * invented here; add them in the admin.
 *
 *   php artisan db:seed --class=ProposedSiteSeeder
 */
class ProposedSiteSeeder extends Seeder
{
    /** @var array<string, Page> */
    private array $pages = [];

    private int $userId = 0;

    public function run(SavePage $save, SaveMenu $saveMenu, SettingsRepository $settings): void
    {
        $user = User::role('super-admin')->first() ?? User::first();
        if (! $user) {
            $this->command?->error('Create an admin user first (set ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD and run db:seed).');

            return;
        }

        $this->userId = $user->id;
        $this->leadership();

        foreach ($this->definitions() as $path => [$title, $parent, $blocks, $intro]) {
            $this->pages[$path] = $this->upsert($save, $user, $path, $title, $parent, $blocks, $intro);
        }

        $settings->set('identity', 'tagline', 'Heritage you can Taste');

        $this->menus($saveMenu);
        $retired = $this->retireLegacyPages();

        Cache::forget('sitemap.xml');
        Cache::flush();
        ResponseCache::clear();

        $this->command?->info(count($this->pages)." pages published, {$retired} legacy pages archived (with redirects); header menu is now About, Operations, ESG, News, Contact.");
    }

    // ---- pages --------------------------------------------------------------------------------------------

    /** @return array<string, array{0: string, 1: ?string, 2: list<array<string, mixed>>, 3: ?string}> */
    private function definitions(): array
    {
        return [
            'home' => ['Home', null, $this->home(), null],
            'about' => ['About', null, $this->about(), 'One hundred and fifty years of Bangladeshi tea, and what has grown from it.'],
            'about/our-story' => ['Our Story', 'about', $this->story(), 'From a cotton trader in Calcutta to one of Bangladesh\'s largest private tea producers.'],
            'about/leadership' => ['Leadership', 'about', [$this->b('people_grid', ['heading' => 'Our team', 'group' => 'leadership'])], null],
            'operations' => ['Operations', null, $this->operations(), 'What we produce, how we make it, and how to work with us.'],
            'operations/tea' => ['Tea', 'operations', $this->tea(), 'CTC tea from the Sylhet region, sold at auction in Chattogram.'],
            'operations/mineral-water' => ['Mineral Water', 'operations', $this->mineralWater(), 'Natural mineral water from Duncan Products Ltd, tested every week since 1994.'],
            'operations/rubber' => ['Rubber', 'operations', $this->rubber(), 'Smoked rubber sheet for Bangladesh\'s footwear and tyre industries, and for export.'],
            'esg' => ['ESG', null, $this->esg(), 'Environmental, social and governance: how we grow responsibly.'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function home(): array
    {
        return [
            $this->b('hero', ['eyebrow' => 'Duncan Brothers Bangladesh', 'heading' => 'Heritage you can Taste', 'slides' => $this->heroSlides()]),
            $this->b('rich_text', ['heading' => 'One of Bangladesh\'s best-known tea producers', 'body' => '<p>At Duncan Brothers, we are proud to be one of the largest and well-known private tea producers in Bangladesh. Our operations span 14 tea estates that produce high-quality CTC teas. We also have two rubber processing plants.</p><p>The majority of our teas are sold through Bangladesh\'s local auctions, principally Chittagong. Our rubber products are supplied directly to local buyers across the region.</p><p><a href="/about">About us</a></p>']),
            $this->b('cards_grid', ['heading' => 'Operations', 'items' => [
                ['title' => 'Tea', 'text' => 'High-quality CTC teas from our estates in the Sylhet region.', 'url' => '/operations/tea'],
                ['title' => 'Rubber', 'text' => 'Smoked rubber sheet, supplied directly to local buyers.', 'url' => '/operations/rubber'],
                ['title' => 'Mineral Water', 'text' => 'Natural mineral water, tested every week, bottled by Duncan Products Ltd.', 'url' => '/operations/mineral-water'],
            ]]),
            $this->b('cta_band', ['heading' => 'Explore our products', 'text' => 'Tea, rubber and mineral water: what we make and how to work with us.', 'button_label' => 'Explore our products', 'button_url' => '/operations']),
            $this->b('cards_grid', ['heading' => 'ESG', 'items' => [
                ['title' => 'Environmental', 'text' => 'Caring for the land we farm.', 'url' => '/esg'],
                ['title' => 'Social', 'text' => 'Our people and the communities around our estates.', 'url' => '/esg'],
                ['title' => 'Governance', 'text' => 'How strong governance shapes the business.', 'url' => '/esg'],
            ]]),
            $this->b('news_feed', ['heading' => 'News', 'limit' => 3]),
        ];
    }

    /**
     * Uploads the 3840x2160 hero scenes (database/seeders/assets/hero, made by tools/hero/generate.py) once and
     * returns them as hero slides. Replace them in the media library with real photography when available.
     *
     * @return list<array{media_id: int, caption: string}>
     */
    private function heroSlides(): array
    {
        $slides = [];
        foreach (['golden' => 'Tea gardens at golden hour', 'morning' => 'Morning light over the tea gardens', 'dusk' => 'Dusk over the tea gardens'] as $key => $alt) {
            $item = MediaItem::where('title', "Hero: {$alt}")->first();
            if (! $item) {
                $item = MediaItem::create(['title' => "Hero: {$alt}", 'alt_text' => $alt, 'credit' => 'Duncan Brothers (Bangladesh) Limited', 'uploaded_by' => $this->userId]);
                $item->addMedia(database_path("seeders/assets/hero/hero-{$key}.jpg"))->preservingOriginal()
                    ->usingFileName(Str::random(24).'.jpg')->usingName($alt)->toMediaCollection(MediaItem::COLLECTION);
            }
            $slides[] = ['media_id' => $item->id, 'caption' => $alt];
        }

        return $slides;
    }

    /** @return list<array<string, mixed>> */
    private function about(): array
    {
        return [
            $this->b('rich_text', ['heading' => 'About Duncan', 'body' => '<p>Duncan Brothers was established 150 years ago when the two Duncan brothers, Walter and William, arrived from Glasgow in 1859 to set up business in Calcutta. Tea was then beginning to become a lucrative business and their focus naturally shifted to tea, which in course of time became their main line of business.</p><p>Many major events including changes of partnership, ownership and mergers have taken place in the one and a half century of Duncan Brothers\' existence prior to the company becoming part of the Lawrie Group Plc., and finally part of Camellia Plc. "Two Leaves and a Bud" were Duncan Brothers\' core business until 1985, when the company decided to diversify by setting up United Insurance and United Leasing, followed by a series of new ventures which gradually turned the company into a group of varied business interests.</p><p>Duncan Brothers is now a major group in Bangladesh with a diverse portfolio employing approximately 18,500 people throughout its organisations.</p><p><a href="/about/our-story">Our Story</a> &nbsp;·&nbsp; <a href="/about/leadership">Leadership</a></p>']),
            $this->b('rich_text', ['heading' => 'Values & governance', 'body' => '<p>We are a commercial business, but our long-term success depends on our people, our communities and the land we farm. Guided by strong governance and our commitment to the communities we serve, we aim to grow responsibly for generations to come.</p><p><a href="/esg">Explore our ESG commitments</a></p>']),
            $this->b('rich_text', ['heading' => 'Associate companies', 'body' => '<p><strong>Rooted in tea, growing beyond it.</strong> Tea was the heart of Duncan Brothers\' business until 1985, when we began to diversify and deepen our long-term commitment to Bangladesh. Building on our infrastructure and experience, we expanded into bottled water and warehousing.</p>']),
            $this->b('company_accordion', ['items' => [
                ['name' => 'Duncan Products Ltd', 'description' => 'Pure water, tested every week since 1994. DPL draws water from its own sources at our two plants in Dhaka and Chattogram, then purifies and bottles it under strict hygienic conditions that meet international standards. Our water meets the requirements of the WHO, the Bangladesh Standards and Testing Institution (BSTI) and the International Bottled Water Association (IBWA). Samples are tested regularly in our own laboratory, every week at ICDDR,B, and further independent testing is carried out in London. ISO 22000 certified; member of IBWA. Email dpl@duncanbd.com, phone 9669251.', 'url' => '/operations/mineral-water'],
                ['name' => 'Chittagong Warehouses Ltd.', 'description' => 'Built for growth. In 2025 we completed a second 14,000 sq ft unit, bringing total capacity to 94,500 sq ft and making CWL one of the largest tea warehouses in Bangladesh. All Duncan Brothers tea is sold at auction in Chattogram, so getting it from our gardens to the warehouse quickly is essential: trucks that once waited up to a week to unload are now turned around in a few hours. The extra space also lets us store teas from other gardens. A legacy since 1950: CWL opened in January 1950 at Fouzderhat, with direct links to the railway and the port, and was expanded in 2002.'],
                ['name' => 'Octavius Steel & Company of Bangladesh Ltd.', 'description' => 'With roots in tea going back more than 125 years, Octavius Steel is today a sponsor shareholder of Chittagong Warehouses Ltd and runs a customs clearing and forwarding agency from its office in Chattogram, working mainly for the Group.'],
                ['name' => 'Duncan Properties Ltd.', 'description' => 'Incorporated and registered in November 2005, Duncan Properties Ltd.\'s primary business is the management of its property in Dhaka.'],
            ]]),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function story(): array
    {
        $events = [
            ['1859', 'A cotton trader arrives in Calcutta', 'Walter Duncan arrives in India and sets up Playfair, Duncan and Company with Patrick Playfair, at 64 Clive Street in Calcutta. The business begins with cotton.'],
            ['1860–61', 'A first taste of tea', 'In the same building, Walter meets Charles Stewart Leckie, agent for two Cachar tea gardens, Doloo and Jalinga. When Leckie leaves India because of ill health, Walter takes on his business and buys shares in Doloo. The Duncans\' long journey in tea begins.'],
            ['1875', 'Duncan Brothers is born', 'Walter and his brother William set up on their own: Walter Duncan and Co in Glasgow, and Duncan Brothers & Company in Calcutta.'],
            ['1890', 'Tea takes over', 'As cotton declines, the brothers turn to tea. By 1890, the agency manages twelve gardens covering 2,350 hectares.'],
            ['1898', 'Octavius Steel enters the story', 'Octavius Steel & Company becomes managing agent for the Lungla Company gardens: Shumshernugger, Lungla, Etah and Karimpore. Seventy years later, it will join the Duncan family.'],
            ['1910', 'The trio estates', 'Duncan Brothers acquires a controlling interest in Allynugger Company and three estates from the McMeekin family: Madhabpur, Patrakhola and Kurma.'],
            ['1919–1945', 'Weathering the storms', 'Through a post-war recession, the slump of 1931–32 and the hardships of the Second World War, the gardens keep going, often with minimal staff and through food shortages.'],
            ['1923', 'Sixty gardens strong', 'The agency now manages around 60 estates, covering 20,000 hectares and producing 14.5 million kg of tea a year.'],
            ['1949', 'A new chapter in the East', 'After partition, Duncan Brothers (Pakistan) Ltd is formed on 20 January 1949, with its head office in Chittagong.'],
            ['1950', 'Chittagong Warehouses opens', 'To get tea to market through its new port, Chittagong Warehouses Ltd opens at Fouzderhat, with links to the railway and the port.'],
            ['1967', 'Two histories come together', 'Duncan Brothers and Octavius Steel come under the same ownership.'],
            ['1970', 'Moving to Dhaka', 'The head office moves from Chittagong to Dhaka.'],
            ['1971', 'Independence and rebuilding', 'The Liberation War leaves the tea industry badly damaged. Duncan Brothers (Bangladesh) Ltd is formed, and a small team of young Bangladeshi planters begins rebuilding: restoring gardens, finding new markets and introducing Bangladesh tea to the world.'],
            ['1974', 'Joining Camellia', 'After years of losses, Lawrie Group Plc takes a controlling interest in the holding company of Duncan\'s Bangladesh estates, and Duncan Brothers becomes part of Camellia Plc. Bangladesh tea begins building an international reputation.'],
            ['1985', 'Growing beyond tea', 'Duncan Brothers diversifies into bottled water, warehousing, insurance and leasing, deepening its long-term commitment to Bangladesh.'],
            ['2025', 'Built for growth', 'A second warehouse unit opens at Chittagong Warehouses, making it one of the largest tea warehouses in the country.'],
        ];

        return [
            $this->b('rich_text', ['heading' => 'It all began with Walter Duncan', 'body' => '<p>Walter Duncan, a Scotsman from Glasgow, sailed from Southampton for Calcutta on 20 November 1858 to start business in India. The firm he set up on arrival, in partnership with Patrick Playfair, was named Messrs Playfair, Duncan and Company, located at 64 Clive Street, now Netaji Subhas Chandra Bose Road. The long story of Duncan Brothers began with Walter Duncan starting a cotton business in January 1859.</p>']),
            $this->b('timeline', ['items' => array_map(fn ($e) => ['year' => $e[0], 'title' => $e[1], 'text' => $e[2]], $events)]),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function operations(): array
    {
        return [
            $this->b('rich_text', ['heading' => 'Operations at a glance', 'body' => '<p>Duncan Brothers manages one Bangladeshi and six UK-based tea companies operating 15 estates across the traditional tea-producing districts of Moulvibazar and Habiganj in the greater Sylhet region. Alongside tea, the company produces and processes rubber in thirteen of these estates. Duncan Brothers also manufactures and markets bottled drinking water from its facilities in Dhaka and Chittagong.</p>']),
            $this->b('cards_grid', ['heading' => 'Our products', 'items' => [
                ['title' => 'Tea', 'text' => 'CTC teas from our Sylhet estates, sold at the Chattogram auction.', 'url' => '/operations/tea'],
                ['title' => 'Rubber', 'text' => 'Smoked rubber sheet from our central factory at Lungla.', 'url' => '/operations/rubber'],
                ['title' => 'Mineral Water', 'text' => 'Natural mineral water from Duncan Products Ltd, tested every week.', 'url' => '/operations/mineral-water'],
            ]]),
            $this->b('certifications', ['heading' => 'Quality & standards', 'items' => [
                ['name' => 'ISO 22000:2005', 'issuer' => 'Tea estates and factories', 'scope' => 'Plantation and cultivation, processing and manufacturing, blending and packing, office management, quality assurance, and the sale and marketing of tea.'],
                ['name' => 'ISO 22000', 'issuer' => 'Duncan Products Ltd', 'scope' => 'Natural mineral water. DPL is also a member of the International Bottled Water Association (IBWA).'],
            ]]),
            $this->b('rich_text', ['heading' => 'Buyers & partners', 'body' => '<h3>How to work with us</h3><p><strong>Tea.</strong> All Duncan Brothers tea is sold through the auction in Chattogram. It is stored and prepared for sale at <a href="/about#associate-companies">Chittagong Warehouses Ltd</a>, so brokers can sample it on time.</p><p><strong>Rubber.</strong> Smoked rubber sheet is supplied directly to buyers in Bangladesh and exported to India, China, Vietnam and Malaysia. <a href="/operations/rubber">Rubber supply details</a>.</p><p><strong>Mineral water.</strong> Orders and procurement go to Duncan Products Ltd: <a href="mailto:dpl@duncanbd.com">dpl@duncanbd.com</a>, +88 02 41062016, +88 02 41062011. <a href="/operations/mineral-water">Mineral water details</a>.</p><p><strong>Compliance.</strong> All our estates and factories are ISO 22000 certified, as is Duncan Products Ltd.</p><p>For a wholesale enquiry, including order sizes, logistics and distribution, please <a href="/contact">contact us</a>.</p>']),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function mineralWater(): array
    {
        $photos = $this->legacyBlock('associate-companies/duncan-products-ltd', 'gallery');

        return array_values(array_filter([
            $this->b('rich_text', ['heading' => 'Pure water, tested every week since 1994', 'body' => '<p>Duncan Products Ltd (DPL) draws water from its own sources at our two plants in Dhaka and Chattogram, then purifies and bottles it under strict hygienic conditions that meet international standards.</p><p>Our water meets the requirements of the World Health Organization (WHO), the Bangladesh Standards and Testing Institution (BSTI) and the International Bottled Water Association (IBWA). We test samples regularly in our own laboratory, and every week at ICDDR,B, the International Centre for Diarrhoeal Disease Research, Bangladesh. Further independent testing is carried out in London.</p>']),
            $this->b('rich_text', ['heading' => 'Our water', 'body' => '<p>Natural mineral water is produced and marketed in 1,500 ml and 500 ml bottles and 20 litre jars. Duncan\'s water has won consumers\' trust for its quality and purity. International organisations, embassies and high commissions, corporate offices, financial institutions and most hotels and restaurants are regular clients, and demand from retail consumers is increasing rapidly.</p>']),
            $this->b('certifications', ['heading' => 'Accreditations', 'items' => [
                ['name' => 'ISO 22000', 'issuer' => 'Duncan Products Ltd', 'scope' => 'Natural mineral water produced and bottled at the Dhaka and Chittagong plants.'],
                ['name' => 'IBWA membership', 'issuer' => 'International Bottled Water Association', 'scope' => 'DPL is a member of the IBWA.'],
            ]]),
            $photos ? $this->b('gallery', ['heading' => 'Photographs', 'items' => $photos['items'] ?? []]) : null,
            $this->b('cta_band', ['heading' => 'Order Duncan\'s water', 'text' => 'dpl@duncanbd.com  ·  +88 02 41062016, +88 02 41062011', 'button_label' => 'Email DPL', 'button_url' => '/contact']),
        ]));
    }

    /** @return list<array<string, mixed>> */
    private function tea(): array
    {
        $estates = ['Amo', 'Nalua', 'Chandpore', 'Mazdehee', 'Allynugger', 'Chatlapore', 'Shumshernugger', 'Lungla', 'Pallakandi', 'Hingajea', 'Etah', 'Karimpore', 'Luskerpore', 'Silloah', 'Rajkie', 'Chaklapunji'];

        return [
            $this->b('stats', ['items' => [
                ['value' => '9,100 ha', 'label' => 'under tea in 16 gardens'],
                ['value' => '12–13m kg', 'label' => 'of tea produced each year'],
                ['value' => '1,400 kg/ha', 'label' => 'average yield, above the national average'],
            ]]),
            $this->b('rich_text', ['heading' => 'Our estates', 'body' => '<p>Duncan Brothers produces 12–13 million kilograms of tea annually from 9,100 hectares across 16 estates in the Sylhet region. With an average yield of 1,400 kg per hectare, the Group consistently performs above the national average, reflecting strong estate management and long-term investment in cultivation.</p>']),
            $this->b('rich_text', ['heading' => 'Cultivation & processing', 'body' => '<p>The Group has undertaken an intensive programme of infilling, planting and replanting with high-yielding clonal plants raised in each estate\'s nurseries. Agricultural practices are continuously improved to maintain healthy bushes and prevent weeds, pests, diseases and waterlogging. Fertiliser is applied in carefully calibrated doses to support optimal growth.</p><p>Plucking standards receive top priority, recognising their critical role in producing high-quality tea. All estates except Hingajea and Pallakandi operate modern factories connected to the national gas grid, enabling efficient power generation and drying through gas-fuelled fluid bed driers. Tea is processed under hygienic conditions with minimal emissions or effluents.</p>']),
            $this->b('rich_text', ['heading' => 'Grades & formats', 'body' => '<p>All estates produce CTC (cut, tear, curl) tea, with two estates equipped to manufacture orthodox tea. This range supports both domestic blenders and export markets. All Duncan Brothers tea is sold at auction in Chattogram, stored and prepared for sale at <a href="/about">Chittagong Warehouses Ltd</a>.</p>']),
            $this->b('certifications', ['heading' => 'Quality standards', 'intro' => 'Our factories are equipped with modern machinery designed to deliver consistent, high-quality output. The tea is clean and grainy in appearance, strong in body and rich in colour: characteristics valued by blenders for reliability and flavour consistency.', 'items' => [
                ['name' => 'ISO 22000:2005', 'scope' => 'All estates and factories are certified for plantation and cultivation, processing and manufacturing, blending and packing, office management, quality assurance, and the sale and marketing of tea.'],
            ]]),
            $this->b('rich_text', ['heading' => 'Market reach & performance', 'body' => '<p>Duncan Brothers\' tea is well regarded among blenders for its high and consistent quality, and the Group also exports tea to international buyers. Ongoing investment in cultivation and processing aims to increase yields and enhance quality, supporting long-term performance and competitiveness.</p>']),
            $this->b('rich_text', ['heading' => 'Tea gardens', 'body' => '<ul>'.implode('', array_map(fn ($e) => "<li>{$e} Tea Estate</li>", $estates)).'</ul>']),
            $this->b('cta_band', ['heading' => 'Talk to us about our tea', 'button_label' => 'Contact', 'button_url' => '/contact']),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function rubber(): array
    {
        $estates = ['Chandpore', 'Mazdehee', 'Allynugger', 'Chatlapore', 'Shumshernugger', 'Lungla', 'Pallakandi', 'Hingajea', 'Etah', 'Karimpore', 'Silloah', 'Rajkie', 'Chaklapunji'];

        return [
            $this->b('stats', ['items' => [
                ['value' => '1,394 ha', 'label' => 'of mature rubber'],
                ['value' => '800+ t', 'label' => 'produced each year'],
                ['value' => '569 ha', 'label' => 'of young rubber coming into production'],
            ]]),
            $this->b('rich_text', ['heading' => 'Processing', 'body' => '<p>Duncan Brothers operates a central rubber factory at Lungla equipped with gas-based firing facilities capable of smoking rubber sheets at scale. This ensures consistent processing quality across all estates.</p>']),
            $this->b('rich_text', ['heading' => 'Product formats', 'body' => '<p>We produce high-quality smoked rubber sheet, processed to meet the needs of industrial buyers in Bangladesh and abroad.</p>']),
            $this->b('rich_text', ['heading' => 'Industrial applications', 'body' => '<p>Our natural rubber is used extensively in Bangladesh\'s growing footwear and tyre industries, where demand continues to rise as manufacturers shift away from synthetic alternatives.</p>']),
            $this->b('rich_text', ['heading' => 'Quality & testing', 'body' => '<p>The quality of our smoked rubber sheet is recognised both locally and internationally, reflected in sustained demand from major rubber-producing countries. Our processing systems ensure consistency, purity and reliable performance across all batches.</p>']),
            $this->b('rich_text', ['heading' => 'Wholesale supply', 'body' => '<p>The Group has 1,394 hectares of mature rubber, producing over 800 metric tons annually. A further 569 hectares of young rubber will come into production in the coming years, strengthening long-term supply capacity.</p><p>Our rubber is sold within Bangladesh and exported to India, China, Vietnam and Malaysia. Duncan Brothers maintains a leading share of rubber production in Bangladesh, with rubber already contributing significantly to company performance and showing strong potential for future growth.</p>']),
            $this->b('rich_text', ['heading' => 'Rubber gardens', 'body' => '<ul>'.implode('', array_map(fn ($e) => "<li>{$e}</li>", $estates)).'</ul>']),
            $this->b('cta_band', ['heading' => 'Buying rubber?', 'text' => 'Rubber is supplied directly to buyers. Get in touch to discuss volumes and delivery.', 'button_label' => 'Contact', 'button_url' => '/contact']),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function esg(): array
    {
        $legacy = fn (string $type, ?string $heading = null) => $this->legacyBlock('sustainability', $type, $heading);
        $plain = fn (?array $block) => collect($block['items'] ?? [])->map(fn ($i) => array_merge($i, ['url' => null]))->all();

        $blocks = [
            $this->b('rich_text', ['heading' => 'Growing responsibly', 'body' => '<p>We are a commercial business, but our long-term success depends on our people, our communities and the land we farm. Guided by strong governance and our commitment to the communities we serve, we aim to grow responsibly for generations to come.</p><p><a href="#environmental">Environmental</a> &nbsp;·&nbsp; <a href="#social">Social</a> &nbsp;·&nbsp; <a href="#governance">Governance</a></p>']),
            $this->b('rich_text', ['heading' => 'Environmental', 'body' => '<p>Duncan Brothers practises green conservation and promotes the protection and proper use of energy and water across all its operations. Our aim has always been to raise awareness and establish best practice for an eco-friendly, energy-efficient production and manufacturing business, with optimum advantage and minimum effect on the environment.</p><p>On our estates, modern factories are connected to the national gas grid and run under hygienic conditions with little or no emission or effluents. Fertiliser is applied in carefully calibrated doses, and agricultural practices keep bushes healthy and control weeds, pests, diseases and waterlogging.</p>']),
        ];
        if ($env = $legacy('cards_grid', 'Environment')) {
            $blocks[] = $this->b('cards_grid', ['items' => $plain($env)]);
        }
        $blocks[] = $this->b('rich_text', ['heading' => 'Social', 'body' => '<p>The welfare of our own employees is very important to the Group, as is the welfare of society in general and of the local communities where we do business. The Group has established the Camellia Duncan Foundation, setting aside funds from its operational income to manage its welfare initiatives: health care, education and community programmes for the people of our estates.</p>']);
        if ($stats = $legacy('stats')) {
            $blocks[] = $this->b('stats', ['items' => $stats['items'] ?? []]);
        }
        if ($people = $legacy('cards_grid', 'People and communities')) {
            $blocks[] = $this->b('cards_grid', ['items' => $plain($people)]);
        }
        $blocks[] = $this->b('rich_text', ['heading' => 'Governance', 'body' => '<p>Strong governance shapes how we run the business, from the field to the factory to the auction. All our estates and factories are ISO 22000 certified, and Duncan Products Ltd is ISO 22000 certified and a member of the International Bottled Water Association.</p>']);
        if ($certs = $legacy('certifications')) {
            $blocks[] = $this->b('certifications', ['heading' => 'Certifications, standards and awards', 'intro' => $certs['intro'] ?? null, 'items' => $certs['items'] ?? []]);
        }
        if ($awards = $legacy('gallery', 'Certificates and awards')) {
            $blocks[] = $this->b('gallery', ['heading' => 'Certificates and awards', 'items' => $awards['items'] ?? []]);
        }

        return $blocks;
    }

    /**
     * A block's data from an existing (possibly archived) page, so real copy and media from the old site carry over.
     *
     * @return array<string, mixed>|null
     */
    private function legacyBlock(string $path, string $type, ?string $heading = null): ?array
    {
        $page = Page::where('path', $path)->first();

        foreach ($page?->blocks ?? [] as $block) {
            if ($block['type'] === $type && ($heading === null || ($block['data']['heading'] ?? null) === $heading)) {
                return $block['data'];
            }
        }

        return null;
    }

    // ---- people -------------------------------------------------------------------------------------------

    private function leadership(): void
    {
        $team = [
            ['Mustafizur Rahman', 'Managing Director'],
            ['Mohammad Mizanur Rahman', 'Financial Director'],
            ['M Fahmidul Islam', 'Sr. Manager, Operations'],
            ['Mehedi Hassan Sabbir', 'Chief of Internal Audit'],
            ['K M Ehteshamur Rashid', 'Dy. Manager, Corporate Affairs (Temporary In-Charge)'],
            ['S M Saidur Rahman', 'Manager, IT'],
            ['M Zahirul Islam', 'SGM & Head of Garden Operations'],
            ['Dr. M Aminul Islam', 'Manager, Welfare (Temporary In-Charge)'],
            ['Ehtesham M Chowdhury', 'Head of Marketing & Chattogram Operations'],
        ];

        foreach ($team as $i => [$name, $position]) {
            Person::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'position' => $position, 'group' => 'leadership', 'sort_order' => $i + 1, 'is_active' => true]);
        }
    }

    // ---- plumbing -----------------------------------------------------------------------------------------

    /** @param list<array<string, mixed>> $blocks */
    private function upsert(SavePage $save, User $user, string $path, string $title, ?string $parentPath, array $blocks, ?string $intro): Page
    {
        $existing = Page::where('path', $path === 'home' ? 'home' : $path)->first();
        $parent = $parentPath ? Page::where('path', $parentPath)->first() : null;

        $page = $save($existing, [
            'title' => $title,
            'slug' => basename($path),
            'parent_id' => $parent?->id,
            'blocks' => $blocks,
            'seo' => $intro ? ['description' => $intro] : null,
        ], $user, 'Proposed site structure (Oct 2026)');

        $page->update(['status' => PageStatus::Published, 'published_at' => $page->published_at ?? now()]);

        return $page;
    }

    /**
     * Pages of the old site that the proposed structure replaces. They are ARCHIVED, not deleted (restore them
     * from the admin if needed), drop out of the site, the sitemap and search, and 301 to their new home.
     *
     * @return int number of pages archived
     */
    private function retireLegacyPages(): int
    {
        $map = [
            'about-duncan' => '/about', 'about-duncan/history-of-duncan' => '/about/our-story', 'about-duncan/lawrie-group' => '/about/our-story', 'about-duncan/camellia-plc' => '/about',
            'associate-companies' => '/about', 'associate-companies/cwl' => '/about', 'associate-companies/duncan-products-ltd' => '/operations/mineral-water',
            'associate-companies/duncan-properties-ltd' => '/about', 'associate-companies/octavius' => '/about', 'associate-companies/ufl' => '/about', 'associate-companies/uic' => '/about',
            'corporate-responsibility' => '/esg', 'corporate-responsibility/cdf-hospital' => '/esg', 'corporate-responsibility/cdf-orchid-project' => '/esg',
            'corporate-responsibility/cdf-school' => '/esg', 'corporate-responsibility/environment' => '/esg', 'corporate-responsibility/other-social-commitments' => '/esg',
            'corporate-responsibility/primary-health-care' => '/esg', 'corporate-responsibility/yasser-life-guard' => '/esg',
            'sustainability' => '/esg', 'gallery' => '/',
            'products' => '/operations', 'products/natural-mineral-water' => '/operations/mineral-water',
            'products/tea' => '/operations/tea', 'products/tea/gardens' => '/operations/tea', 'products/tea/production' => '/operations/tea',
            'products/rubber' => '/operations/rubber', 'products/rubber/gardens' => '/operations/rubber', 'products/rubber/production' => '/operations/rubber',
        ];

        $count = 0;
        foreach ($map as $path => $to) {
            $page = Page::where('path', $path)->first();
            if (! $page) {
                continue;
            }
            if ($page->status !== PageStatus::Archived) {
                $page->update(['status' => PageStatus::Archived]);
                PageContentChanged::dispatch($page);
                $count++;
            }
            Redirect::updateOrCreate(['from_path' => '/'.$path], ['to_url' => $to, 'status_code' => 301, 'is_automatic' => false]);
        }

        // The old per-business and per-estate pages (/what-we-do/...) are not in the proposed structure: the Tea,
        // Rubber and Operations pages replace them. Deactivated (not deleted) and redirected.
        Redirect::updateOrCreate(['from_path' => '/what-we-do'], ['to_url' => '/operations', 'status_code' => 301, 'is_automatic' => false]);
        foreach (Business::with('estates')->get() as $business) {
            $business->update(['is_active' => false]);
            $to = match ($business->slug) { 'tea' => '/operations/tea', 'rubber' => '/operations/rubber', 'natural-mineral-water' => '/operations/mineral-water', default => '/operations' };
            Redirect::updateOrCreate(['from_path' => '/what-we-do/'.$business->slug], ['to_url' => $to, 'status_code' => 301, 'is_automatic' => false]);
            foreach ($business->estates as $estate) {
                Redirect::updateOrCreate(['from_path' => '/what-we-do/'.$business->slug.'/estates/'.$estate->slug], ['to_url' => $to, 'status_code' => 301, 'is_automatic' => false]);
            }
        }

        return $count;
    }

    private function menus(SaveMenu $save): void
    {
        $id = fn (string $path) => $this->pages[$path]->id;

        // Top level and sub-items follow the document's site map. Section items are anchors on their parent page.
        $tree = [
            ['About', $id('about'), [
                ['Our Story', $id('about/our-story')],
                ['Leadership', $id('about/leadership')],
                ['Values & Governance', $id('esg')],
                ['Associate Companies', '/about#associate-companies'],
            ]],
            ['Operations', $id('operations'), [
                ['Tea', $id('operations/tea')],
                ['Rubber', $id('operations/rubber')],
                ['Mineral Water', $id('operations/mineral-water')],
                ['Buyers & Partners', '/operations#buyers-partners'],
            ]],
            ['ESG', $id('esg'), [
                ['Environmental', '/esg#environmental'],
                ['Social', '/esg#social'],
                ['Governance', '/esg#governance'],
            ]],
            ['News', '/news', []],
            ['Contact', '/contact', []],
        ];

        $items = [];
        $link = fn ($target) => is_int($target) ? ['page_id' => $target] : ['url' => $target];
        foreach ($tree as [$label, $target, $children]) {
            $parentIndex = count($items);
            $items[] = ['label' => $label, ...$link($target)];
            foreach ($children as [$childLabel, $childTarget]) {
                $items[] = ['label' => $childLabel, 'parent' => $parentIndex, ...$link($childTarget)];
            }
        }

        $save(MenuLocation::Explore, $items);

        $save(MenuLocation::Footer, [
            ['label' => 'Our Story', 'page_id' => $id('about/our-story')],
            ['label' => 'Careers', 'url' => '/careers/vacancies'],
            ['label' => 'Contact', 'url' => '/contact'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{type: string, data: array<string, mixed>}
     */
    private function b(string $type, array $data): array
    {
        return ['type' => $type, 'data' => $data];
    }
}
