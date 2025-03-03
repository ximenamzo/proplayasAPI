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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->enum('role_type', ['admin', 'node_leader', 'member']);
            $table->enum('node_type', ['sociedad_civil', 'empresarial', 'cientifico', 'funcion_publica', 'individual'])->nullable();
            $table->foreignId('node_id')->nullable()->constrained('nodes')->onDelete('cascade');
            $table->text('token')->nullable();
            $table->enum('status', ['pendiente', 'aceptada', 'expirada'])->default('pendiente');
            $table->timestamp('sent_date');
            $table->timestamp('accepted_date')->nullable();
            $table->timestamp('expiration_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
