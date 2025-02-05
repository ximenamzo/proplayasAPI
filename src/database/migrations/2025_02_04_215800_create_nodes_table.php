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
            $table->enum('type', ['cientifico', 'empresarial', 'activista']);
            $table->string('code')->unique();
            $table->string('name');
            $table->string('country');
            $table->string('city');
            $table->year('joined_in');
            $table->integer('members_count')->nullable();
            $table->string('leader_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('website')->nullable();
            $table->enum('activity_level', ['alta', 'media', 'baja'])->nullable();
            $table->string('memorandum')->nullable();
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
