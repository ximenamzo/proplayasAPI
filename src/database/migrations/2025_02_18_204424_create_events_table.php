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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['event', 'webinar', 'taller', 'clase', 'curso', 'seminario', 'foro', 'conferencia', 'congreso']);
            $table->text('description');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('date');
            $table->string('link')->nullable();
            $table->enum('format', ['presencial', 'online']);
            $table->string('location')->nullable();
            $table->string('file_path')->nullable();
            $table->string('cover_image')->nullable();
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
        Schema::dropIfExists('events');
    }
};
