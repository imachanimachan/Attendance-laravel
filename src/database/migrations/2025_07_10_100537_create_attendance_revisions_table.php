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
        Schema::create('attendance_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->date('applied_on');
            $table->datetime('original_clock_in');
            $table->datetime('original_clock_out');
            $table->datetime('revised_clock_in');
            $table->datetime('revised_clock_out');
            $table->text('note');
            $table->unsignedTinyInteger('status')->default(1); // 1: 承認待ち, 2: 承認済み
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_revisions');
    }
};
