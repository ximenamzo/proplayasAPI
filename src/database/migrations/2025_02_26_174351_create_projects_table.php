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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('date');
            $table->string('location')->nullable();
            $table->string('link')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('file_path')->nullable();
            $table->json('participants')->nullable(); // JSON
            $table->enum('status', ['publico', 'archivado'])->default('publico');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
