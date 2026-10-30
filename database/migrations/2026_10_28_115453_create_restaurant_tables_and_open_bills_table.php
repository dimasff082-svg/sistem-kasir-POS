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
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->string('table_number')->unique();
            $table->string('name');
            $table->string('status')->default('available'); // available, occupied
            $table->timestamps();
        });

        Schema::create('open_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->unique();
            $table->string('table_number')->index();
            $table->string('status')->default('open'); // open, paid
            $table->foreignId('opened_by')->constrained('users');
            $table->dateTime('opened_at');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_bills');
        Schema::dropIfExists('restaurant_tables');
    }
};
