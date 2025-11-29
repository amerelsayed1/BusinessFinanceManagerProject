<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'type')) {
                $table->string('type')->default('cash')->after('name');
            }

            if (!Schema::hasColumn('accounts', 'opening_balance')) {
                $table->decimal('opening_balance', 12, 2)->default(0)->after('type');
            }

            if (!Schema::hasColumn('accounts', 'current_balance')) {
                $table->decimal('current_balance', 12, 2)->default(0)->after('opening_balance');
            }
        });

        // Copy existing balance values into the new columns if present
        if (Schema::hasColumn('accounts', 'balance')) {
            DB::table('accounts')->update([
                'opening_balance' => DB::raw('balance'),
                'current_balance' => DB::raw('balance'),
            ]);

            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('balance');
            });
        }
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'balance')) {
                $table->decimal('balance', 12, 2)->default(0)->after('name');
            }
        });

        // Restore balance from current_balance when available
        if (Schema::hasColumn('accounts', 'current_balance')) {
            DB::table('accounts')->update([
                'balance' => DB::raw('current_balance'),
            ]);
        }

        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('accounts', 'opening_balance')) {
                $table->dropColumn('opening_balance');
            }
            if (Schema::hasColumn('accounts', 'current_balance')) {
                $table->dropColumn('current_balance');
            }
        });
    }
};
