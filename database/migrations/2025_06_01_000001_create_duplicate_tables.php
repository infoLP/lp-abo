<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duplicate_groups', function (Blueprint $table) {
            $table->id();
            $table->string('match_type', 50);         // email, siret, name_postal, phone, company_city
            $table->string('match_value', 255)->nullable();
            $table->unsignedTinyInteger('confidence_score')->default(0);
            $table->enum('status', ['pending', 'merged', 'dismissed'])->default('pending');
            $table->unsignedSmallInteger('clients_count')->default(0);
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('match_type');
            $table->index('confidence_score');
        });

        Schema::create('duplicate_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duplicate_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_master')->default(false);
            $table->timestamps();

            $table->unique(['duplicate_group_id', 'client_id']);
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_group_items');
        Schema::dropIfExists('duplicate_groups');
    }
};
