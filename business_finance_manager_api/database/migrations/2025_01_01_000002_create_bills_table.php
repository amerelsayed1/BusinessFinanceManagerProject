<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->string('status');
            $table->foreignId('account_id')->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->text('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
