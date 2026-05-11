<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Adresse de facturation (snapshot)
            $table->string('billing_name')->nullable()->after('beneficiary_id');
            $table->string('billing_address1')->nullable()->after('billing_name');
            $table->string('billing_address2')->nullable()->after('billing_address1');
            $table->string('billing_address3')->nullable()->after('billing_address2');
            $table->string('billing_postal_code', 10)->nullable()->after('billing_address3');
            $table->string('billing_city')->nullable()->after('billing_postal_code');
            $table->string('billing_cedex')->nullable()->after('billing_city');
            $table->string('billing_country', 2)->default('FR')->after('billing_cedex');
        });

        // order_id sur subscriptions si pas encore présent
        if (!Schema::hasColumn('subscriptions', 'order_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->foreignId('order_id')
                      ->nullable()
                      ->after('id')
                      ->constrained('orders')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'billing_name','billing_address1','billing_address2','billing_address3',
                'billing_postal_code','billing_city','billing_cedex','billing_country',
            ]);
        });
    }
};
