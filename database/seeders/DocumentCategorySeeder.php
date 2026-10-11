<?php

namespace Database\Seeders;

use App\Domain\Documents\Models\DocumentCategory;
use Illuminate\Database\Seeder;

class DocumentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Annual reports', 'annual-reports', true],
            ['Interim reports', 'interim-reports', true],
            ['AGM & EGM notices', 'agm-egm-notices', true],
            ['Statutory disclosures', 'statutory-disclosures', true],
            ['Policies', 'policies', false],
            ['Sustainability reports', 'sustainability-reports', false],
        ];

        foreach ($categories as $order => [$name, $slug, $investor]) {
            DocumentCategory::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_investor' => $investor, 'sort_order' => $order]);
        }
    }
}
