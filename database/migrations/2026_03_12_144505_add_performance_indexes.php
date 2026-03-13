<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('columns', function (Blueprint $table) {
            $table->index(['board_id', 'position']);
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->index(['column_id', 'position']);
            $table->index('assigned_user_id');
            $table->index('priority');
            $table->index('due_date');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->index(['board_id', 'created_at']);
            $table->index('target_type');
        });
    }

    public function down(): void
    {
        Schema::table('columns', function (Blueprint $table) {
            $table->dropIndex(['board_id', 'position']);
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->dropIndex(['column_id', 'position']);
            $table->dropIndex(['assigned_user_id']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['due_date']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['board_id', 'created_at']);
            $table->dropIndex(['target_type']);
        });
    }
};
