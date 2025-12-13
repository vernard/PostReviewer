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
        Schema::create('approval_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['approval_request_id', 'email']);
            $table->index('token');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->json('default_reviewers')->nullable()->after('settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_invites');

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('default_reviewers');
        });
    }
};
