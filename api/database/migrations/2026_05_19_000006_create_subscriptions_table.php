<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Both nullable: "subscribe to ALL banks for USD" => bank_id NULL.
            // "subscribe to PrivatBank, any currency" => currency_id NULL.
            // "subscribe to PrivatBank/USD" => both set.
            // "global" => both NULL.
            $table->foreignId('bank_id')->nullable()->constrained('banks')->cascadeOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->cascadeOnDelete();

            // User-tunable trigger; defaults to the global default (5%).
            $table->decimal('threshold_pct', 5, 2)->default(5.0);
            $table->timestamps();

            // Prevent duplicate subscriptions for the same user+target.
            // MySQL treats multiple NULLs as distinct, so duplicate-global rows are
            // still possible — we guard against that in the FormRequest layer.
            $table->unique(['user_id', 'bank_id', 'currency_id'], 'subscriptions_unique_target');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
