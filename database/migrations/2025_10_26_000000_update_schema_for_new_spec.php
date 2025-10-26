<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // districts: unique code + index name
        Schema::table('districts', function (Blueprint $table) {
            if (!Schema::hasColumn('districts', 'code')) {
                $table->string('code')->unique();
            } else {
                $table->string('code')->unique()->change();
            }
            $table->index('name');
        });

        // villages: FK not null, unique code, indexes
        Schema::table('villages', function (Blueprint $table) {
            if (Schema::hasColumn('villages', 'district_id')) {
                $table->unsignedBigInteger('district_id')->nullable(false)->change();
            } else {
                $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            }
            if (Schema::hasColumn('villages', 'code')) {
                $table->string('code')->unique()->change();
            } else {
                $table->string('code')->unique();
            }
            $table->index('name');
            $table->index('district_id');
            $table->index(['district_id', 'name']);
        });

        // destinations: meta + soft deletes + indexes
        Schema::table('destinations', function (Blueprint $table) {
            if (!Schema::hasColumn('destinations', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('name');
            }
            if (!Schema::hasColumn('destinations', 'meta_description')) {
                $table->string('meta_description')->nullable()->after('meta_title');
            }
            if (!Schema::hasColumn('destinations', 'deleted_at')) {
                $table->softDeletes();
            }
            $table->index('user_id');
            $table->index('name');
            $table->index('created_at');
        });

        // detail_destinations: adjust columns per spec
        Schema::table('detail_destinations', function (Blueprint $table) {
            if (Schema::hasColumn('detail_destinations', 'village')) {
                $table->dropColumn('village');
            }
            if (Schema::hasColumn('detail_destinations', 'district')) {
                $table->dropColumn('district');
            }
            if (!Schema::hasColumn('detail_destinations', 'district_id')) {
                $table->foreignId('district_id')->nullable()->after('address')->constrained('districts')->nullOnDelete();
            }
            if (!Schema::hasColumn('detail_destinations', 'village_id')) {
                $table->foreignId('village_id')->nullable()->after('address')->constrained('villages')->nullOnDelete();
            }
            if (Schema::hasColumn('detail_destinations', 'maps_link')) {
                $table->renameColumn('maps_link', 'map_url');
            } else if (!Schema::hasColumn('detail_destinations', 'map_url')) {
                $table->text('map_url')->nullable()->after('village_id');
            }
            // Preserve existing ticket_price if present; only add if missing
            if (!Schema::hasColumn('detail_destinations', 'ticket_price')) {
                $table->decimal('ticket_price', 10, 2)->nullable()->after('map_url');
            }
            if (!Schema::hasColumn('detail_destinations', 'currency')) {
                // Avoid AFTER referencing a column added in the same ALTER
                $table->string('currency', 3)->nullable();
            }
            if (Schema::hasColumn('detail_destinations', 'open_hours')) {
                $table->dropColumn('open_hours');
            }
            if (Schema::hasColumn('detail_destinations', 'close_hours')) {
                $table->dropColumn('close_hours');
            }
            if (Schema::hasColumn('detail_destinations', 'pic')) {
                $table->dropColumn('pic');
            }
            if (!Schema::hasColumn('detail_destinations', 'published_at')) {
                $table->dateTime('published_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('detail_destinations', 'deleted_at')) {
                $table->softDeletes();
            }
            $table->index('destination_id');
            $table->index('village_id');
            $table->index('status');
            $table->index('published_at');
        });

        // destination_images: alt_text, sort_order, soft deletes, indexes
        Schema::table('destination_images', function (Blueprint $table) {
            if (!Schema::hasColumn('destination_images', 'alt_text')) {
                $table->string('alt_text')->nullable()->after('caption');
            }
            if (!Schema::hasColumn('destination_images', 'is_cover')) {
                $table->boolean('is_cover')->default(false)->after('alt_text');
            }
            if (!Schema::hasColumn('destination_images', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_cover');
            }
            if (!Schema::hasColumn('destination_images', 'deleted_at')) {
                $table->softDeletes();
            }
            $table->index('destination_id');
            $table->index('is_cover');
            $table->index(['destination_id', 'is_cover', 'sort_order']);
        });

        // categories: ensure index on name
        Schema::table('categories', function (Blueprint $table) {
            $table->index('name');
        });

        // destination_open_hours
        if (!Schema::hasTable('destination_open_hours')) {
            Schema::create('destination_open_hours', function (Blueprint $table) {
                $table->id();
                $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week');
                $table->time('open_time')->nullable();
                $table->time('close_time')->nullable();
                $table->boolean('is_closed')->default(false);
                $table->string('notes')->nullable();
                $table->timestamps();
                $table->unique(['destination_id', 'day_of_week']);
            });
        }

        // facilities
        if (!Schema::hasTable('facilities')) {
            Schema::create('facilities', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('icon')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('destination_facility')) {
            Schema::create('destination_facility', function (Blueprint $table) {
                $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
                $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
                $table->primary(['destination_id', 'facility_id']);
            });
        }

        // tags
        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->timestamps();
                $table->index('name');
            });
        }
        if (!Schema::hasTable('destination_tag')) {
            Schema::create('destination_tag', function (Blueprint $table) {
                $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
                $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
                $table->primary(['destination_id', 'tag_id']);
            });
        }

        // destination_ratings summary
        if (!Schema::hasTable('destination_ratings')) {
            Schema::create('destination_ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('destination_id')->unique()->constrained('destinations')->cascadeOnDelete();
                $table->integer('ratings_count')->default(0);
                $table->decimal('ratings_avg', 3, 2)->default(0);
                $table->timestamps();
            });
        }

        // user_favorites
        if (!Schema::hasTable('user_favorites')) {
            Schema::create('user_favorites', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
                $table->timestamp('created_at')->useCurrent();
                $table->primary(['user_id', 'destination_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_favorites');
        Schema::dropIfExists('destination_ratings');
        Schema::dropIfExists('destination_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('destination_facility');
        Schema::dropIfExists('facilities');
        Schema::dropIfExists('destination_open_hours');
        // Revert of changes on existing tables is skipped intentionally.
    }
};
