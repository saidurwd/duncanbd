<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('category', 20)->index();
            $table->string('name', 120);
            $table->string('email', 255);
            $table->string('phone', 40)->nullable();
            $table->string('organisation', 160)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_note')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 160)->unique();
            $table->string('department', 120)->nullable();
            $table->string('location', 120)->nullable();
            $table->string('employment_type', 40)->default('full_time');
            $table->longText('description')->nullable();
            $table->longText('requirements')->nullable();
            $table->date('closing_date')->nullable()->index();
            $table->string('status', 12)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('email', 255);
            $table->string('phone', 40)->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('cv_path', 500);
            $table->string('cv_original_name', 255);
            $table->string('cv_mime', 120);
            $table->unsignedBigInteger('cv_size');
            $table->string('status', 20)->default('new')->index();
            $table->text('internal_note')->nullable();
            $table->timestamp('consent_at');
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
        Schema::dropIfExists('vacancies');
        Schema::dropIfExists('subscribers');
        Schema::dropIfExists('enquiries');
    }
};
