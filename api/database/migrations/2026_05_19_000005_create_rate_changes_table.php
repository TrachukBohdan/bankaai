<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_id')->nullable()->constrained('banks')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->string('market', 16)->default('cash');
            $table->string('source', 16);
            $table->string('side', 8); // 'buy' | 'sell'

            $table->decimal('previous_value', 14, 6);
            $table->decimal('new_value', 14, 6);
            $table->decimal('delta_pct', 8, 4); // signed, e.g. -5.7321
            $table->decimal('threshold_pct', 5, 2); // threshold that triggered the row

            $table->timestamp('observed_at')->index();
            $table->timestamps();

            $table->index(['bank_id', 'currency_id', 'observed_at']);
            $table->index(['currency_id', 'observed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_changes');
    }
};
