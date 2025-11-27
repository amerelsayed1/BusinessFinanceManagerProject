<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('monthly_sales')) {
            Schema::create('monthly_sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->integer('month');
                $table->integer('year');
                $table->decimal('total_sales', 15, 2);
                $table->timestamps();

                $table->unique(['user_id', 'month', 'year']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_sales');
    }
};
