<?php

namespace Database\Seeders;

use App\Domain\Cms\Actions\SavePage;
use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\MenuRepository;
use App\Domain\Cms\Models\Menu;
use App\Domain\Cms\Models\MenuItem;
use App\Domain\Cms\Models\Page;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Seeder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The "Sustainability" overview page: people, environment, and the certifications and awards the old website
 * mentions. Every statement is taken from the old site's pages (Corporate Responsibility, Environment, Primary
 * Health Care, CDF Hospital/School, Yasser Life Guard, Tea Production, Duncan Products). Nothing is added.
 *
 * Idempotent: creates /sustainability, or rewrites it if it already exists. Also adds it to the Explore menu.
 *
 *   php artisan db:seed --class=SustainabilityPageSeeder
 */
class SustainabilityPageSeeder extends Seeder
{
    public function run(SavePage $savePage): void
    {
        $user = User::role('super-admin')->first() ?? User::first();
        if (! $user) {
            $this->command->error('Create an admin user first.');

            return;
        }

        $image = fn (string $legacyFile) => Media::where('custom_properties->legacy_file', $legacyFile)->value('model_id');
        $items = fn (array $files) => array_values(array_filter(array_map(fn ($f) => ($id = $image($f)) ? ['media_id' => (int) $id] : null, $files)));
        $shade = $image('1486444602_section_with_new_shade_sets.jpg');
        $link = fn (string $path) => Page::where('path', $path)->exists() ? '/'.$path : null;

        $blocks = [
            ['type' => 'hero', 'data' => ['heading' => 'Sustainability', 'intro' => 'People and the environment play a significant role in our corporate policy.', 'slides' => $items(['1486444602_section_with_new_shade_sets.jpg', '1486449433_cdf_hospital.jpg', '1486449381_cdfs_students.jpg'])]],
            ['type' => 'rich_text', 'data' => ['heading' => 'Our approach', 'body' => '<p>Duncan Brothers is a commercial organisation, however people and the environment play a significant role in its corporate policy. We work to improve the quality of our products and our productivity and to be cost efficient, with a focus on our employees and their welfare. The welfare of society in general, of the local communities where we do business and of the environment in which we operate also forms an integral part of corporate policy.</p><p>The Group has established the Camellia Duncan Foundation, setting aside funds from its operational income, to manage its welfare initiatives.</p>']],
            ['type' => 'stats', 'data' => ['items' => [
                ['value' => '17,500', 'label' => 'workers covered by Primary Health Care (with 65,000 dependents)'],
                ['value' => '50', 'label' => 'beds at the Camellia Duncan Foundation Hospital'],
                ['value' => '3,250', 'label' => 'students in garden-run and aided primary schools'],
                ['value' => '742', 'label' => 'lives saved by Yasser Life Guard at Cox\'s Bazar'],
            ]]],
            ['type' => 'cards_grid', 'data' => ['heading' => 'People and communities', 'items' => [
                ['title' => 'Primary Health Care', 'text' => 'Launched in 1989, the first programme of its kind in the private sector, now covering all 16 tea estates.', 'media_id' => $image('1486449615_clinic.jpg'), 'url' => $link('corporate-responsibility/primary-health-care')],
                ['title' => 'Camellia Duncan Foundation Hospital', 'text' => 'A 50-bed referral hospital inaugurated in 1994, with specialist cancer treatment.', 'media_id' => $image('1486449433_cdf_hospital.jpg'), 'url' => $link('corporate-responsibility/cdf-hospital')],
                ['title' => 'Camellia Duncan Foundation School', 'text' => 'A residential secondary school for workers\' children, inaugurated in 2004.', 'media_id' => $image('1486449341_cdf_school.jpg'), 'url' => $link('corporate-responsibility/cdf-school')],
                ['title' => 'Orchid Project', 'text' => 'Orchids grown at Shahbazpur to help finance the Foundation\'s charitable work.', 'media_id' => $image('1486391126_orchid_garden.jpg'), 'url' => $link('corporate-responsibility/cdf-orchid-project')],
                ['title' => 'Yasser Life Guard', 'text' => 'A life-saving service on the Cox\'s Bazar beach, the first of its kind in the country.', 'url' => $link('corporate-responsibility/yasser-life-guard')],
                ['title' => 'Other social commitments', 'text' => 'Support for art and for sport, including chess and the Paralympics.', 'url' => $link('corporate-responsibility/other-social-commitments')],
            ]]],
            ['type' => 'cards_grid', 'data' => ['heading' => 'Environment', 'items' => [
                ['title' => 'Water', 'text' => 'Drinking water is supplied through piped water, hand tube wells and wells. Where needed, water is purified or disinfected before use, and rain water and groundwater are primarily used for cultivation.', 'url' => $link('corporate-responsibility/environment')],
                ['title' => 'Conservation', 'text' => 'Afforestation, rain-water storage in lakes and ponds, and awareness programmes among garden residents about avoiding waste.', 'media_id' => $shade, 'url' => $link('corporate-responsibility/environment')],
                ['title' => 'Energy', 'text' => 'Energy-efficient technology in tea production, energy-saving bulbs in all factories and accommodation, and efficient cooking stoves that cut firewood use.', 'url' => $link('corporate-responsibility/environment')],
                ['title' => 'Geo-diversity', 'text' => 'Care for the soil so that fertiliser and pesticide use does not upset the natural ecology; certain lands are used for rubber, afforestation and soil-enrichment programmes.', 'url' => $link('corporate-responsibility/environment')],
            ]]],
            ['type' => 'certifications', 'data' => [
                'heading' => 'Certifications, standards and awards',
                'intro' => 'As stated on our existing website. Certificate details are kept up to date by the Company Secretary.',
                'items' => [
                    ['name' => 'ISO 22000:2005', 'issuer' => 'Duncan Brothers tea gardens and factories', 'scope' => 'All the Group\'s gardens and factories are certified for plantation and cultivation, processing and manufacturing, blending and packing, office management, quality assurance, and the sale and marketing of tea.'],
                    ['name' => 'ISO 22000', 'issuer' => 'Duncan Products Ltd.', 'scope' => 'Natural mineral water produced and bottled at the Dhaka and Chittagong plants.'],
                    ['name' => 'IBWA membership', 'issuer' => 'International Bottled Water Association', 'scope' => 'Duncan Products is the only company in Bangladesh that is a member of the IBWA.'],
                    ['name' => 'Water quality standards', 'issuer' => 'WHO, BSTI and IBWA requirements', 'scope' => 'Bottled water meets the requirements of the World Health Organisation, the Bangladesh Standards and Testing Institution and the IBWA, and is tested regularly in the company\'s own laboratory and every week at ICDDR,B, with further tests in London.'],
                    ['name' => 'RTZ World Development Award', 'issuer' => 'Centre for World Development Education, U.K.', 'year' => '1991', 'scope' => 'Awarded to Duncan Brothers for its long-term commitment to the Primary Health Care programme.'],
                ],
            ]],
            ['type' => 'gallery', 'data' => ['heading' => 'Certificates and awards', 'items' => $items(['cwl.jpg', 'db_vat.jpg'])]],
            ['type' => 'cta_band', 'data' => ['heading' => 'Read more about our responsibilities', 'text' => 'Health care, education, the environment and our other social commitments.', 'button_label' => 'Corporate responsibility', 'button_url' => '/corporate-responsibility']],
        ];

        $page = Page::where('path', 'sustainability')->first();
        $page = $savePage($page, ['title' => 'Sustainability', 'slug' => 'sustainability', 'blocks' => $blocks, 'seo' => [
            'title' => 'Sustainability, certifications and community programmes',
            'description' => 'How Duncan Brothers cares for people and the environment: Primary Health Care, a hospital and school, water and energy conservation, and our ISO 22000 certification.',
            'og_media_id' => $shade,
        ]], $user, 'Sustainability overview (sourced from the old website)');
        $page->update(['status' => 'published', 'published_at' => $page->published_at ?? now()]);

        $this->addToMenus($page);
    }

    private function addToMenus(Page $page): void
    {
        foreach ([MenuLocation::Explore->value => 'Corporate Responsibility', MenuLocation::Footer->value => null] as $location => $after) {
            $menu = Menu::where('location', $location)->first();
            if (! $menu || $menu->items()->where('page_id', $page->id)->exists()) {
                continue;
            }

            $anchor = $after ? $menu->items()->where('label', $after)->whereNull('parent_id')->first() : null;
            $order = $anchor ? $anchor->sort_order + 1 : (int) $menu->items()->max('sort_order') + 1;
            $menu->items()->where('sort_order', '>=', $order)->increment('sort_order');
            MenuItem::create(['menu_id' => $menu->id, 'label' => 'Sustainability', 'page_id' => $page->id, 'sort_order' => $order, 'is_active' => true]);
        }

        app(MenuRepository::class)->forget();
    }
}
