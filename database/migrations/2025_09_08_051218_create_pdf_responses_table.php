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
        Schema::create('pdf_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('pdf_assignments')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('pdf_fields')->cascadeOnDelete();
            $table->text('value')->nullable(); // text/date value or base64 signature
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_responses');
    }
};
