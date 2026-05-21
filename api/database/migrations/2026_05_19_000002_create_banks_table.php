<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            // Upstream identifiers — kept separately because MinFin and finance.ua
            // do not use the same slug for every bank (e.g. "aval" vs "raiffeisen-bank-aval").
            $table->string('minfin_slug')->nullable()->unique();
            $table->string('finance_ua_slug')->nullable()->unique();

            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('legal_address')->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_date')->nullable();
            $table->decimal('rating', 3, 1)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
