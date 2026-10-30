<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'image')) {
                $table->string('image')->nullable()->after('name');
            }
            if (Schema::hasColumn('products', 'purchase_price')) {
                $table->dropColumn('purchase_price');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'unit_purchase_price')) {
                $table->dropColumn('unit_purchase_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'purchase_price')) {
                $table->decimal('purchase_price', 15, 2)->default(0);
            }
            if (Schema::hasColumn('products', 'image')) {
                $table->dropColumn('image');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'unit_purchase_price')) {
                $table->decimal('unit_purchase_price', 15, 2)->default(0);
            }
        });
    }
};
