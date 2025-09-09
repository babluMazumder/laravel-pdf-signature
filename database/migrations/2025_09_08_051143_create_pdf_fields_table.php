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
        Schema::create('pdf_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('pdf_templates')->cascadeOnDelete();
            $table->string('type'); // text, date, signature
            $table->integer('page');
            $table->float('x');
            $table->float('y');
            $table->float('width');
            $table->float('height');
            $table->boolean('required')->default(false);
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_fields');
    }
};
