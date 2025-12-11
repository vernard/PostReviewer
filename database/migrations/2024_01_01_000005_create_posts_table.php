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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title'); // Internal title for organization
            $table->text('caption')->nullable();
            $table->json('platforms'); // ['facebook_feed', 'instagram_feed', etc.]
            $table->enum('status', [
                'draft',
                'pending_approval',
                'changes_requested',
                'approved',
                'archived'
            ])->default('draft');
            $table->timestamp('scheduled_for')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['brand_id', 'status']);
            $table->index(['created_by', 'status']);
        });

        // Post media pivot table
        Schema::create('post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->unsignedSmallInteger('position')->default(0);
            $table->json('platform_overrides')->nullable(); // Per-platform crops/settings
            $table->timestamps();

            $table->unique(['post_id', 'media_id']);
            $table->index(['post_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_media');
        Schema::dropIfExists('posts');
    }
};
