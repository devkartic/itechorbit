<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('basics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('designation');
            $table->string('mobile');
            $table->string('address');
            $table->string('service_offering_title');
            $table->string('career_objective');
            $table->text('self_introduction')->nullable();
            $table->text('additional_information')->nullable();
            $table->string('banner_image_path')->nullable();
            $table->string('profile_image_path')->nullable();
            $table->string('cv_path')->nullable();
            $table->tinyInteger('cv_public_status')->default(0);
            $table->float('experience_year');
            $table->date('birth_date');
            $table->string('gender', 10);
            $table->string('marital_status', 10);
            $table->tinyInteger('status')->default(0);
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
        Schema::dropIfExists('basics');
    }
};
