<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('detail_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->text('description');
            $table->string('address');
            $table->string('village');
            $table->string('district');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('ticket_price', 10, 2)->nullable();
            $table->string('open_hours')->nullable();
            $table->string('close_hours')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('phone')->nullable();
            $table->enum('status', ['draft', 'published'])->default('published');
            $table->timestamps();
        });

        Schema::create('destination_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->string('image_url');
            $table->string('caption')->nullable();
            $table->boolean('is_cover')->default(false);
            $table->timestamps();
        });

        Schema::create('category_destination', function (Blueprint $table) {
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->primary(['destination_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_destination');
        Schema::dropIfExists('destination_images');
        Schema::dropIfExists('detail_destinations');
        Schema::dropIfExists('destinations');
    }
};

