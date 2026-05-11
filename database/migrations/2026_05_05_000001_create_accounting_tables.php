<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Zones géographiques utilisées sur toutes les tables
    private const ZONES = [
        'metropole',
        'corse',
        'dom',
        'ue_sans_intracom',
        'ue_avec_intracom',
        'international',
    ];

    public function up(): void
    {
        // ── 1. Référentiel maître des codes comptables ──────────────────────
        Schema::create('accounting_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('label');
            $table->string('description')->nullable();
            $table->enum('type', ['vente', 'tva', 'livraison', 'autre'])
                  ->default('autre')
                  ->comment('Catégorie du compte');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── 2. Taux de TVA avec compte + taux par zone ──────────────────────
        Schema::create('vat_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // "Taux particulier"
            $table->string('slug')->unique(); // "taux_particulier" (usage interne)
            $table->string('usage')           // "Incluse dans les prix"
                  ->default('Incluse dans les prix');

            foreach (self::ZONES as $zone) {
                $table->string("{$zone}_accounting_code", 20)
                      ->nullable()
                      ->comment("Code compte TVA — {$zone}");
                $table->decimal("{$zone}_rate", 5, 2)
                      ->nullable()
                      ->comment("Taux TVA % — {$zone}");
            }

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── 3. Affectations comptables (ventes + livraisons) ─────────────────
        Schema::create('accounting_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('label');          // "Vente Abonnement EJG"
            $table->enum('type', ['abonnement', 'revue', 'livraison']);
            $table->foreignId('magazine_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            // Référence au taux de TVA applicable
            $table->foreignId('vat_rate_id')
                  ->nullable()
                  ->constrained('vat_rates')
                  ->nullOnDelete();

            foreach (self::ZONES as $zone) {
                $table->string("{$zone}_accounting_code", 20)
                      ->nullable()
                      ->comment("Code compte — {$zone}");
            }

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── 4. Codes auxiliaires clients ─────────────────────────────────────
        Schema::create('auxiliary_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('label');
            $table->string('description')->nullable();
            $table->foreignId('magazine_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── 5. Sections analytiques ───────────────────────────────────────────
        Schema::create('analytical_sections', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('label');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytical_sections');
        Schema::dropIfExists('auxiliary_codes');
        Schema::dropIfExists('accounting_assignments');
        Schema::dropIfExists('vat_rates');
        Schema::dropIfExists('accounting_codes');
    }
};
