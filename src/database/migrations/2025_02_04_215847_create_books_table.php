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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('author');
            $table->date('publication_date')->nullable();
            $table->string('isbn')->nullable();
            $table->text('description');
            $table->string('link')->nullable();
            $table->string('file_path')->nullable();
            $table->string('cover_image')->nullable();
            $table->enum('creator_type', ['admin', 'nodo', 'miembro']);
            $table->integer('creator_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
