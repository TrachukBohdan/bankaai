<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table): void {
            $table->id();
            // bank_id is nullable: NBU rows have no bank.
            $table->foreignId('bank_id')->nullable()->constrained('banks')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();

            // MinFin returns two markets: "cash" (exchange office) and "card".
            // NBU rows always carry market = "official".
            $table->string('market', 16)->default('cash');
            $table->string('source', 16); // 'minfin' | 'nbu'

            $table->decimal('buy', 14, 6)->nullable();
            $table->decimal('sell', 14, 6)->nullable();

            $table->timestamp('rate_at')->index();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->index(['bank_id', 'currency_id', 'market', 'rate_at']);
            $table->index(['currency_id', 'rate_at']);
            $table->index(['source', 'rate_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
