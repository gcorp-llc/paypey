<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('paypay_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('driver')->unique();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('paypay_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('driver');
            $table->unsignedBigInteger('amount');
            $table->string('currency', 10)->default('IRR');
            $table->string('authority')->nullable()->index();
            $table->string('transaction_id')->nullable()->index();
            $table->string('status')->index();
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paypay_transactions');
        Schema::dropIfExists('paypay_gateways');
    }
};
