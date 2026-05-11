<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->index();         // company, invoice, subscriptions, emails
            $table->string('key', 100)->unique();          // ex: company.name, invoice.primary_color
            $table->string('label', 150);                  // Libellé affiché
            $table->text('description')->nullable();       // Aide contextuelle
            $table->enum('type', [
                'text', 'textarea', 'email', 'url',
                'color', 'boolean', 'number',
                'image', 'select',
            ])->default('text');
            $table->text('value')->nullable();             // Valeur stockée (toujours string)
            $table->text('default_value')->nullable();     // Valeur par défaut
            $table->json('options')->nullable();           // Pour type=select
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
