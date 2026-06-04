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
        Schema::create('rider_profiles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
             $table->string('legal_name');
           // $table->string('sur_name');
           // $table->string('last_name');
           // $table->string('other_name')->nullable();
            $table->string('mobile_number');
            $table->string('service_zone');
            $table->string('nin');
            $table->string('gender');
            $table->string('state');
             $table->string('current_latitude')->nullabel();
            $table->string('current_longitude')->nullable( );
            $table->string('review_rank')->nullable();
            $table->string('is_available');
            $table->string('mobility_type')->default('bike'); // bike, van
            $table->string('mobility_brand')->nullable(); // honda, baja, carter
            $table->string('mobility_model')->nullable(); //carter 100
            $table->string('production_year')->nullable();
            $table->string('total_trips')->default('0');
            $table->string('current_lat')->nullable();
            $table->string('current_lng')->nullable();
            $table->string('plate_number');
            $table->string('status')->default('inactive'); //active, suspended, banned
            $table->string('image')->nullable();
            $table->timestamps();
        });

    }

    /** 
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_profiles');
    }
};
