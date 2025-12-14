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
        Schema::create('agency_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('creator'); // Role within this agency
            $table->timestamps();

            $table->unique(['user_id', 'agency_id']);
        });

        // Migrate existing users to the pivot table
        DB::statement('
            INSERT INTO agency_user (user_id, agency_id, role, created_at, updated_at)
            SELECT id, agency_id, role, NOW(), NOW()
            FROM users
            WHERE agency_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_user');
    }
};
