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
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leader_id')->constrained('users')->onDelete('cascade');
            $table->string('code')->unique();
            $table->enum('type', ['sociedad_civil', 'empresarial', 'cientifico', 'funcion_publica', 'individual']);
            $table->string('name');
            $table->string('profile_picture')->nullable();
            $table->string('country');
            $table->string('city');
            $table->string('coordinates')->nullable();
            $table->text('alt_places')->nullable();
            $table->year('joined_in');
            $table->integer('members_count')->nullable();
            $table->string('id_photo')->nullable();
            $table->string('node_email')->nullable();
            $table->string('website')->nullable();
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->string('youtube')->nullable();
            $table->string('memorandum')->nullable();
            $table->enum('status', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
