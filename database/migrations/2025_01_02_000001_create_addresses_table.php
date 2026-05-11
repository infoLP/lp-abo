<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            // Identification
            $table->string('name')->default('Adresse n°1');         // Nom de l'adresse
            $table->enum('address_type', ['particulier', 'entreprise'])->default('particulier');
            $table->enum('usage', ['billing', 'delivery', 'both'])->default('both');
            $table->boolean('is_default')->default(false);

            // Lignes RNVP
            $table->string('l1')->nullable();   // Destinataire (civilité + nom ou raison sociale)
            $table->string('l2')->nullable();   // Complément identification (appt, étage...)
            $table->string('l3')->nullable();   // Complément distribution (résidence, bât...)
            $table->string('l4')->nullable();   // Numéro + libellé de voie
            $table->string('l5')->nullable();   // Lieu-dit / distribution spéciale
            $table->string('l6_postal_code', 10)->nullable();
            $table->string('l6_city')->nullable();
            $table->string('l6_cedex')->nullable();
            $table->string('l6_state_code')->nullable();
            $table->string('l7_country', 2)->default('FR');

            // RNVP
            $table->boolean('rnvp_valid')->nullable();
            $table->timestamp('rnvp_checked_at')->nullable();
            $table->string('rnvp_status')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
