<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dzly_hook_otp_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('otp')->nullable();
            $table->string('mobile')->nullable();
            $table->string('serial_number', 225)->nullable();
       
            $table->string('locale')->default('ar');
            $table->enum('status', ['pending', 'verified'])->default('pending');
            $table->morphs('model');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dzly_hook_otp_requests');
    }
};
