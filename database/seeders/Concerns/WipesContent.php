<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/** Empties every content table (never users, roles or permissions) and the stored files. */
trait WipesContent
{
    protected function wipeContent(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['menu_items', 'menus', 'redirects', 'not_found_log', 'revisions', 'pages', 'document_versions', 'documents', 'posts', 'news_categories',
            'estates', 'businesses', 'people', 'timeline_events', 'applications', 'vacancies', 'subscribers', 'enquiries', 'media', 'media_items', 'media_folders', 'settings'] as $table) {
            DB::table($table)->truncate();
        }
        Schema::enableForeignKeyConstraints();

        File::ensureDirectoryExists(storage_path('app/public'));
        File::cleanDirectory(storage_path('app/public'));
        File::ensureDirectoryExists(storage_path('app/private/documents'));
        File::cleanDirectory(storage_path('app/private/documents'));
    }
}
