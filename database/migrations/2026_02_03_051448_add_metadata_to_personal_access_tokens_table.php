<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // expires_at already exists, only add new columns
            $table->unsignedBigInteger('created_by')->nullable()->after('tokenable_id');
            $table->json('metadata')->nullable()->after('abilities');

            // Add index for performance
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropColumn(['created_by', 'metadata']);
        });
    }
};
