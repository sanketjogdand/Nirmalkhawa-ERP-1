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
            $table->boolean('is_locked')->default(false)->after('created_by');
            $table->foreignId('locked_by')->nullable()->after('is_locked')->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('locked_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn(['is_locked', 'locked_at']);
        });
    }
};
