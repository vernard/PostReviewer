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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug');
            $table->string('logo')->nullable();
            $table->json('color_scheme')->nullable();
            $table->string('profile_name')->nullable(); // For mockup preview
            $table->string('profile_avatar')->nullable(); // For mockup preview
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['agency_id', 'slug']);
        });

        // Pivot table for user-brand access
        Schema::create('brand_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['brand_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_user');
        Schema::dropIfExists('brands');
    }
};
