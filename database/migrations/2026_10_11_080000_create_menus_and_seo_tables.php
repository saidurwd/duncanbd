<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('location', 40)->unique();
            $table->string('name', 80);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('label', 80);
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('url', 500)->nullable();
            $table->string('target', 10)->default('_self');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 500)->unique();
            $table->string('to_url', 500);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_automatic')->default(false);
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });

        Schema::create('not_found_log', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500)->unique();
            $table->string('referrer', 500)->nullable();
            $table->unsignedBigInteger('hits')->default(1);
            $table->timestamp('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_log');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
