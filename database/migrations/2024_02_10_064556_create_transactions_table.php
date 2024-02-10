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
            $table->unsignedBigInteger('source_crypto_account_id');
            $table->unsignedBigInteger('destination_crypto_account_id');
            $table->string('coin_type'); 
            $table->decimal('amount', 18, 8);
            $table->timestamps();
            $table->foreign('source_crypto_account_id')->references('id')->on('crypto_accounts')->onDelete('cascade');
            $table->foreign('destination_crypto_account_id')->references('id')->on('crypto_accounts')->onDelete('cascade');
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
