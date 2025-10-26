<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'village_id')) {
                $table->foreignId('village_id')->nullable()->after('location')->constrained('villages')->nullOnDelete();
            }
            if (Schema::hasColumn('events', 'start_date') && !Schema::hasColumn('events', 'start_at')) {
                // rename requires doctrine/dbal; fallback: add new column and leave old
                $table->dateTime('start_at')->nullable()->after('longitude');
            } elseif (!Schema::hasColumn('events', 'start_at')) {
                $table->dateTime('start_at')->nullable();
            }
            if (Schema::hasColumn('events', 'end_date') && !Schema::hasColumn('events', 'end_at')) {
                $table->dateTime('end_at')->nullable()->after('start_at');
            } elseif (!Schema::hasColumn('events', 'end_at')) {
                $table->dateTime('end_at')->nullable();
            }
            if (!Schema::hasColumn('events', 'timezone')) {
                $table->string('timezone')->default('Asia/Jakarta');
            }
            if (!Schema::hasColumn('events', 'organizer_name')) {
                $table->string('organizer_name')->nullable();
            }
            if (!Schema::hasColumn('events', 'organizer_contact')) {
                $table->string('organizer_contact')->nullable();
            }
            if (!Schema::hasColumn('events', 'registration_url')) {
                $table->string('registration_url')->nullable();
            }
            if (!Schema::hasColumn('events', 'is_free')) {
                $table->boolean('is_free')->default(true);
            }
            if (!Schema::hasColumn('events', 'price_min')) {
                $table->decimal('price_min', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('events', 'price_max')) {
                $table->decimal('price_max', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('events', 'meta_title')) {
                $table->string('meta_title')->nullable();
            }
            if (!Schema::hasColumn('events', 'meta_description')) {
                $table->string('meta_description')->nullable();
            }
            if (!Schema::hasColumn('events', 'deleted_at')) {
                $table->softDeletes();
            }

            $table->index('user_id');
            $table->index('title');
            $table->index('village_id');
            $table->index('start_at');
            $table->index('end_at');
        });

        if (!Schema::hasTable('event_categories')) {
            Schema::create('event_categories', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->timestamps();
                $table->index('name');
            });
        }
        if (!Schema::hasTable('event_event_category')) {
            Schema::create('event_event_category', function (Blueprint $table) {
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->foreignId('event_category_id')->constrained('event_categories')->cascadeOnDelete();
                $table->primary(['event_id', 'event_category_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_event_category');
        Schema::dropIfExists('event_categories');
        // Do not drop added columns for safety
    }
};

