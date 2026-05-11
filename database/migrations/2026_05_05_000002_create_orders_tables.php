<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Commandes ────────────────────────────────────────────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Numérotation : CMD + AAMM + séquentiel
            $table->string('number', 20)->unique()->nullable();

            // Client payeur
            $table->foreignId('client_id')
                  ->constrained('clients')->restrictOnDelete();

            // Bénéficiaire (peut être différent du payeur)
            $table->foreignId('beneficiary_id')
                  ->nullable()
                  ->constrained('clients')->nullOnDelete();

            // Adresse de livraison (snapshot au moment de la commande)
            $table->string('delivery_company')->nullable();
            $table->string('delivery_recipient')->nullable();
            $table->string('delivery_address1')->nullable();
            $table->string('delivery_address2')->nullable();
            $table->string('delivery_postal_code', 10)->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_country', 2)->default('FR');

            // Statut
            $table->enum('status', ['brouillon', 'validee', 'installee', 'annulee'])
                  ->default('brouillon');

            // Dates
            $table->date('order_date')->default(now());
            $table->date('validated_at')->nullable();
            $table->date('installed_at')->nullable();

            // Montants
            $table->decimal('subtotal', 10, 2)->default(0);   // HT avant remise
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('total_ht', 10, 2)->default(0);
            $table->decimal('total_tva', 10, 2)->default(0);
            $table->decimal('total_ttc', 10, 2)->default(0);

            // Facturation liée
            $table->foreignId('invoice_id')
                  ->nullable()
                  ->constrained('invoices')->nullOnDelete();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // ── Lignes de commande ────────────────────────────────────────────────
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('magazine_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_plan_id')
                  ->constrained()->restrictOnDelete();

            // Dates calculées selon la formule
            $table->date('start_date');
            $table->date('end_date')->nullable();          // pour les formules durée
            $table->integer('issues_count')->nullable();   // pour les formules au numéro

            // Prix
            $table->decimal('unit_price', 10, 2);
            $table->decimal('tva_rate', 5, 2)->default(2.10);
            $table->decimal('total_ht', 10, 2);
            $table->decimal('total_tva', 10, 2);
            $table->decimal('total_ttc', 10, 2);

            // Support (copie depuis la formule au moment de la commande)
            $table->string('support')->nullable();

            // Référence à l'abonnement créé lors de l'installation
            $table->foreignId('subscription_id')
                  ->nullable()
                  ->constrained()->nullOnDelete();

            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
