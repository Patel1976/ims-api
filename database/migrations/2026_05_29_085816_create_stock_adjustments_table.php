<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict');
            $table->date('date');
            $table->enum('type', ['addition', 'subtraction']);
            $table->enum('reason', ['received', 'damaged', 'expired', 'count', 'other']);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
