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
        Schema::create('exports', function (Blueprint $table) {
            $table->id();

            $table->string('service')->nullable();
            $table->string('userId')->nullable();
            $table->string('videoId')->nullable();
            $table->string('videoAspectRatio')->nullable();
            $table->string('videoResolution')->nullable();
            $table->boolean('videoOutro')->default(false);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->string('file_path')->nullable();
            $table->longText('process_output')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
