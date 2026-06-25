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
        Schema::create('job_items', function (Blueprint $table) {
            $table->id();
              $table->bigInteger('job_id')->unsigned();
              $table->string('title');
             $table->string('receiver_name')->nullable();
            $table->text('description')->nullable();
            $table->string('pickup_address');
            $table->string('pickup_lat')->nullable();
            $table->string('pickup_lng')->nullable();
             $table->string('dropoff_lat')->nullable();
            $table->string('dropoff_lng')->nullable();
            $table->string('dropoff_address');
            $table->string('mobility_type_needed')->nullable();
            $table->string('price')->nullable();
            $table->string('platform_fee')->nullable();
            $table->string('order_fee')->nullable();
            $table->string('total_fair_fee')->nullable();
            $table->string('price_type')->default('fixed');//fixed, negotiable,
             $table->string('status')->nullable(); //open, matched, in_progress, completed, cancelled
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_items');
    }
};
