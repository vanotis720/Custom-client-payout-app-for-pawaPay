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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id')->nullable()->index();
            $table->string('dhru_user')->nullable()->index();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10);
            $table->string('pawapay_reference')->unique();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending')->index();
            $table->enum('type', ['collection', 'payout'])->index();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->unique(['invoice_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
