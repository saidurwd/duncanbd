<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 160)->unique();
            $table->string('summary', 500)->nullable();
            $table->longText('body')->nullable();
            $table->json('metrics')->nullable();
            $table->foreignId('logo_media_id')->nullable()->constrained('media_items')->nullOnDelete();
            $table->string('website_url', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('estates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('slug', 160);
            $table->string('district', 120)->nullable();
            $table->decimal('area_ha', 10, 2)->nullable();
            $table->string('factory', 160)->nullable();
            $table->json('certifications')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('photo_media_id')->nullable()->constrained('media_items')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'slug']);
        });

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 160)->unique();
            $table->string('position', 160)->nullable();
            $table->string('group', 20)->default('leadership')->index();
            $table->longText('bio')->nullable();
            $table->foreignId('photo_media_id')->nullable()->constrained('media_items')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('timeline_events', function (Blueprint $table) {
            $table->id();
            $table->string('year', 10);
            $table->string('title', 160);
            $table->text('body')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media_items')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline_events');
        Schema::dropIfExists('people');
        Schema::dropIfExists('estates');
        Schema::dropIfExists('businesses');
    }
};
