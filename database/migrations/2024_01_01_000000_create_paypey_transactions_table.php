<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paypey_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('gateway')->index();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('toman');
            $table->string('status', 20)->default('pending')->index();
            $table->string('transaction_id')->nullable()->index();
            $table->string('ref_id')->nullable()->index();
            $table->string('card_number', 30)->nullable();
            $table->string('description')->nullable();
            $table->text('callback_url')->nullable();
            $table->json('metadata')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paypey_transactions');
    }
};
