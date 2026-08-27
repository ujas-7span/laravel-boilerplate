<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->string('disk', 32)->default('public');
            $table->string('directory')->default('');
            $table->string('filename');
            $table->string('extension', 32)->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->string('aggregate_type', 32)->default('all');
            $table->unsignedBigInteger('size')->default(0);
            $table->json('custom_properties')->nullable();
            $table->timestamps();

            $table->index(['directory', 'filename', 'extension']);
            $table->index('disk');
            $table->index('aggregate_type');
        });

        Schema::create('mediables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->morphs('mediable');
            $table->string('tag', 64)->default('default');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['media_id', 'mediable_type', 'mediable_id', 'tag'], 'mediables_unique_attachment');
            $table->index(['mediable_type', 'mediable_id', 'tag']);
        });

        Schema::create('temp_files', function (Blueprint $table): void {
            $table->id();
            $table->string('disk', 32)->default('public');
            $table->string('directory')->default('');
            $table->string('filename');
            $table->string('tag', 64)->default('default');
            $table->timestamps();

            $table->index('created_at');
            $table->index(['directory', 'filename']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_files');
        Schema::dropIfExists('mediables');
        Schema::dropIfExists('media');
    }
};
