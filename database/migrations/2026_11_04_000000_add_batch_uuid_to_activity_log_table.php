<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spatie/laravel-activitylog v4 (the PHP 8.3 compatible line) also writes a batch id.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('activity_log', 'batch_uuid')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->uuid('batch_uuid')->nullable()->after('properties');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('activity_log', 'batch_uuid')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropColumn('batch_uuid');
            });
        }
    }
};
