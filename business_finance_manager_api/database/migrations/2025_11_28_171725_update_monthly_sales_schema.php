<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('monthly_sales')) {
            // Drop legacy unique constraint if it exists
            DB::statement('ALTER TABLE monthly_sales DROP CONSTRAINT IF EXISTS monthly_sales_user_id_month_year_unique');

            // Convert month integer/year pair into a single date column on the first day of the month
            if (Schema::hasColumn('monthly_sales', 'year')) {
                DB::statement("ALTER TABLE monthly_sales ALTER COLUMN month TYPE DATE USING make_date(year, month, 1)");
                DB::statement("ALTER TABLE monthly_sales ALTER COLUMN month SET NOT NULL");
                Schema::table('monthly_sales', function (Blueprint $table) {
                    $table->dropColumn('year');
                });
            }

            Schema::table('monthly_sales', function (Blueprint $table) {
                if (!Schema::hasColumn('monthly_sales', 'product_cost')) {
                    $table->decimal('product_cost', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('monthly_sales', 'ads_expenses')) {
                    $table->decimal('ads_expenses', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('monthly_sales', 'logistics_cost')) {
                    $table->decimal('logistics_cost', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('monthly_sales', 'platform_fees')) {
                    $table->decimal('platform_fees', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('monthly_sales', 'other_expenses')) {
                    $table->decimal('other_expenses', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('monthly_sales', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });

            DB::statement('ALTER TABLE monthly_sales DROP CONSTRAINT IF EXISTS monthly_sales_user_month_unique');
            DB::statement('ALTER TABLE monthly_sales ADD CONSTRAINT monthly_sales_user_month_unique UNIQUE (user_id, month)');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('monthly_sales')) {
            DB::statement('ALTER TABLE monthly_sales DROP CONSTRAINT IF EXISTS monthly_sales_user_month_unique');

            Schema::table('monthly_sales', function (Blueprint $table) {
                if (Schema::hasColumn('monthly_sales', 'notes')) {
                    $table->dropColumn('notes');
                }
                foreach (['product_cost', 'ads_expenses', 'logistics_cost', 'platform_fees', 'other_expenses'] as $col) {
                    if (Schema::hasColumn('monthly_sales', $col)) {
                        $table->dropColumn($col);
                    }
                }
                if (!Schema::hasColumn('monthly_sales', 'year')) {
                    $table->integer('year')->default(2000);
                }
            });

            DB::statement("ALTER TABLE monthly_sales ALTER COLUMN month TYPE INTEGER USING EXTRACT(MONTH FROM month)");
            DB::statement('ALTER TABLE monthly_sales ADD CONSTRAINT monthly_sales_user_id_month_year_unique UNIQUE (user_id, month, year)');
        }
    }
};
