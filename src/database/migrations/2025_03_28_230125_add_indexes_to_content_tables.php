<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'publications',
        'books',
        'webinars',
        'series',
        'news_posts'
    ];

    protected array $columns = [
        'author_id',
        'status',
        'created_at'
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                foreach ($this->columns as $column) {
                    $tableBlueprint->index($column);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                foreach ($this->columns as $column) {
                    $tableBlueprint->dropIndex([$column]);
                }
            });
        }
    }
};
