<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_id')->constrained('banks')->cascadeOnDelete();

            // Stable identifier from finance.ua (their "id" field).
            // Allows idempotent upserts when re-syncing.
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('address');
            $table->string('phone')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->boolean('is_primary')->default(false);

            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['bank_id', 'external_id']);
            $table->index(['bank_id']);
            $table->index(['lat', 'lng']);
        });

        // Native MySQL 8 spatial column + index for fast nearest-branch queries.
        // SQLite (used in the in-memory test suite) does not support generated
        // spatial columns, so we skip this step there. NearestBranchFinder is
        // only exercised against MySQL — its tests live as integration smoke
        // tests rather than unit tests for that reason.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                ALTER TABLE branches
                ADD COLUMN coordinates POINT
                GENERATED ALWAYS AS (ST_GeomFromText(CONCAT('POINT(', lng, ' ', lat, ')'), 4326)) STORED
                NOT NULL SRID 4326
            SQL);
            DB::statement('CREATE SPATIAL INDEX branches_coordinates_spatial ON branches (coordinates)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
