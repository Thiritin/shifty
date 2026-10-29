<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->enum('status', ['available', 'unavailable']);
            $table->unique(['user_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
