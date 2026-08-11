<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The admin UI already offers a "recurring" toggle (e.g. a holiday that
     * falls on the same day every year) but there was no column behind it.
     */
    public function up(): void
    {
        Schema::table('non_working_days', function (Blueprint $table) {
            if (!Schema::hasColumn('non_working_days', 'is_recurring')) {
                $table->boolean('is_recurring')->default(false)->after('date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('non_working_days', function (Blueprint $table) {
            if (Schema::hasColumn('non_working_days', 'is_recurring')) {
                $table->dropColumn('is_recurring');
            }
        });
    }
};
