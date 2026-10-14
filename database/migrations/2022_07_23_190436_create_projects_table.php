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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->string('project_category')->comment('Example: erp, ecommerce')->nullable();
            $table->string('client_name');
            $table->foreignId('skill_id')->comment('Created project based on technology/skill')->constrained('skills')->onUpdate('cascade')->onDelete('cascade');
            $table->string('jobs')->nullable();
            $table->text('technology_used')->nullable();
            $table->string('played_role')->nullable();
            $table->text('about_project');
            $table->string('project_url')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status')->default(1);
            $table->string('order_number')->nullable();
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
        Schema::dropIfExists('projects');
    }
};
