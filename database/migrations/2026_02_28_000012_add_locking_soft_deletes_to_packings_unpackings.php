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
        Schema::table('packings', function (Blueprint $table) {
            if (! Schema::hasColumn('packings', 'deleted_at')) {
                $table->softDeletes()->after('locked_at');
            }
        });

        Schema::table('unpackings', function (Blueprint $table) {
            if (! Schema::hasColumn('unpackings', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('created_by');
            }
            if (! Schema::hasColumn('unpackings', 'locked_by')) {
                $table->foreignId('locked_by')->nullable()->after('is_locked')->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            }
            if (! Schema::hasColumn('unpackings', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('locked_by');
            }
            if (! Schema::hasColumn('unpackings', 'deleted_at')) {
                $table->softDeletes()->after('locked_at');
            }
        });

        Schema::table('packing_items', function (Blueprint $table) {
            if (! Schema::hasColumn('packing_items', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('unpacking_items', function (Blueprint $table) {
            if (! Schema::hasColumn('unpacking_items', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packing_items', function (Blueprint $table) {
            if (Schema::hasColumn('packing_items', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('unpacking_items', function (Blueprint $table) {
            if (Schema::hasColumn('unpacking_items', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('unpackings', function (Blueprint $table) {
            if (Schema::hasColumn('unpackings', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('unpackings', 'locked_by')) {
                $table->dropConstrainedForeignId('locked_by');
            }
            if (Schema::hasColumn('unpackings', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
            if (Schema::hasColumn('unpackings', 'locked_at')) {
                $table->dropColumn('locked_at');
            }
        });

        Schema::table('packings', function (Blueprint $table) {
            if (Schema::hasColumn('packings', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
