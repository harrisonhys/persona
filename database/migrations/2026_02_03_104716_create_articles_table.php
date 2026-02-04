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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content');
            $table->longText('content_rewrite')->nullable();

            // Non-relational fields (comma-separated strings)
            $table->string('category')->nullable();
            $table->string('label')->nullable();

            // Publishing workflow
            $table->boolean('is_reviewed')->default(false);
            $table->timestamp('published_at')->nullable();

            // Audit trail (store names directly as strings)
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('edited_by')->nullable();
            $table->string('published_by')->nullable();
            $table->string('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('slug');
            $table->index('is_reviewed');
            $table->index('published_at');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
