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
        Schema::table('expenses', function (Blueprint $table) {
            // Remove is_ads if it exists
            if (Schema::hasColumn('expenses', 'is_ads')) {
                $table->dropColumn('is_ads');
            }

            // Add category_id
            $table->foreignId('category_id')->nullable()->after('account_id')->constrained('expense_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            $table->boolean('is_ads')->default(false);
        });
    }
};
