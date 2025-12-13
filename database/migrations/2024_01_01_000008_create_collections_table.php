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
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('approval_token', 64)->unique()->nullable(); // For public approval link
            $table->timestamp('approval_token_expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'created_at']);
            $table->index('approval_token');
        });

        // Add collection_id to posts table
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('collection_id')->nullable()->after('brand_id')->constrained()->onDelete('set null');
            $table->index('collection_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropColumn('collection_id');
        });

        Schema::dropIfExists('collections');
    }
};
