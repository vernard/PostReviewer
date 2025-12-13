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
        Schema::create('homepage_usages', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('session_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // file_upload, export
            $table->string('platform')->nullable(); // instagram_feed, facebook_story, etc.
            $table->string('media_type')->nullable(); // image, video
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['ip_address', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_usages');
    }
};
