<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();

            // Add index for better query performance
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'date']);

        });

        // Populate user_id from account relationship
        DB::statement('
            UPDATE bills
            SET user_id = (
                SELECT user_id
                FROM accounts
                WHERE accounts.id = bills.account_id
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['user_id', 'date']);
            $table->dropColumn('user_id');
        });
    }
};
