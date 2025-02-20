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
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['boletin', 'guia', 'articulo']);
            $table->string('title');
            $table->text('description');
            $table->string('link')->nullable();
            $table->string('doi')->nullable();
            $table->string('issn')->nullable();
            $table->string('file_path')->nullable();
            $table->string('cover_image')->nullable();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['publico', 'archivado'])->default('publico');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
