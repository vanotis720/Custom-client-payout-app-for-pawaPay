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
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('payout_id')->nullable()->unique()->after('id');
            $table->string('currency', 10)->default('CDF')->after('amount');
            $table->string('provider')->nullable()->after('phone_number');
            $table->string('description')->nullable()->after('provider');
            $table->json('raw_response')->nullable()->after('pawapay_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['payout_id', 'currency', 'provider', 'description', 'raw_response']);
        });
    }
};
