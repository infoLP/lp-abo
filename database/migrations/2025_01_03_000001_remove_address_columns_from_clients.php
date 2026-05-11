<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'address_name',
                'address_line1',
                'address_line2',
                'address_line3',
                'postal_code',
                'city',
                'cedex',
                'country',
                'delivery_address_name',
                'delivery_address_line1',
                'delivery_address_line2',
                'delivery_address_line3',
                'delivery_postal_code',
                'delivery_city',
                'delivery_cedex',
                'delivery_country',
                'use_delivery_address',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('address_name')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('address_line3')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('cedex')->nullable();
            $table->string('country', 2)->default('FR');
            $table->string('delivery_address_name')->nullable();
            $table->string('delivery_address_line1')->nullable();
            $table->string('delivery_address_line2')->nullable();
            $table->string('delivery_address_line3')->nullable();
            $table->string('delivery_postal_code', 10)->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_cedex')->nullable();
            $table->string('delivery_country', 2)->nullable();
            $table->boolean('use_delivery_address')->default(false);
        });
    }
};
