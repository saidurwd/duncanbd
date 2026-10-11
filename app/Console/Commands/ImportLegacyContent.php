<?php

namespace App\Console\Commands;

use App\Domain\Cms\Legacy\LegacyImporter;
use App\Domain\Identity\Models\User;
use Illuminate\Console\Command;

class ImportLegacyContent extends Command
{
    protected $signature = 'legacy:import {--path=storage/app/legacy : Folder holding manifest.json and files/}';

    protected $description = 'Import the crawled legacy duncanbd.com content (see tools/legacy/crawl.py) as draft CMS content';

    public function handle(LegacyImporter $importer): int
    {
        $option = (string) $this->option('path');
        $path = str_starts_with($option, '/') ? rtrim($option, '/') : base_path($option);
        $manifestFile = $path.'/manifest.json';

        if (! is_file($manifestFile)) {
            $this->error("No manifest at {$manifestFile}. Run: python3 tools/legacy/crawl.py");

            return self::FAILURE;
        }

        $user = User::role('super-admin')->first() ?? User::first();
        if (! $user) {
            $this->error('Create an admin user first (db:seed with ADMIN_SEED_EMAIL set).');

            return self::FAILURE;
        }

        $importer->import(json_decode(file_get_contents($manifestFile), true, flags: JSON_THROW_ON_ERROR), $path.'/files', $user);

        $this->table(['Imported', 'Count'], collect($importer->stats)->map(fn ($v, $k) => [str_replace('_', ' ', $k), $v])->values()->all());

        if ($importer->pendingContent) {
            $this->warn('Pages created as placeholders (source not readable when crawled):');
            foreach ($importer->pendingContent as $line) {
                $this->line(' - '.$line);
            }
        }

        $this->info('Everything was created as DRAFT. Review in the admin, replace placeholders and image alt texts, then publish.');

        return self::SUCCESS;
    }
}
