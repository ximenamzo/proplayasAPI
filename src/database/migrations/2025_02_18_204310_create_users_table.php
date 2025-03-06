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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'node_leader', 'member']);
            $table->text('about')->nullable();
            $table->string('degree')->nullable();
            $table->string('postgraduate')->nullable();
            $table->string('expertise_area')->nullable();
            $table->string('research_work')->nullable();
            $table->string('profile_picture')->nullable();
            $table->json('social_media')->nullable();
            $table->enum('status', ['activo', 'inactivo'])->default('activo');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
