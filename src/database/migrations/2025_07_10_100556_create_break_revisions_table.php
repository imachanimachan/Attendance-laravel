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
        Schema::create('break_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('break_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_revision_id')->constrained()->cascadeOnDelete();
            $table->datetime('original_break_start')->nullable();
            $table->datetime('original_break_end')->nullable();
            $table->datetime('revised_break_start')->nullable();
            $table->datetime('revised_break_end')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('break_revisions');
    }
};
