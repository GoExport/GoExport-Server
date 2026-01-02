<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('export_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('export_settings')->insert([
            [
                'key' => 'aspect_ratios',
                'value' => json_encode([
                    '4:3' => '4:3 (Standard)',
                    '16:9' => '16:9 (Widescreen)',
                    '14:9' => '14:9 (Classic)',
                    '9:16' => '9:16 (Vertical)',
                ]),
                'description' => 'Available video aspect ratios',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'resolutions',
                'value' => json_encode([
                    '240p' => '240p (Low)',
                    '360p' => '360p (SD)',
                    '420p' => '420p',
                    '480p' => '480p',
                    '720p' => '720p (HD)',
                    '1080p' => '1080p (Full HD)',
                    '1440p' => '1440p (2K)',
                    '2k' => '2K',
                    '4k' => '4K (Ultra HD)',
                    '5k' => '5K',
                    '8k' => '8K',
                ]),
                'description' => 'Available video resolutions',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_settings');
    }
};
