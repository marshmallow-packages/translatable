<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('languages')) {
            $this->migrateExistingTable();

            return;
        }

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('active_for_translations')->default(true);
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();
        });
    }

    protected function migrateExistingTable(): void
    {
        if (Schema::hasColumn('languages', 'language') && ! Schema::hasColumn('languages', 'code')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->renameColumn('language', 'code');
            });
        }

        if (! Schema::hasColumn('languages', 'active')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->boolean('active')->default(true)->after('icon');
            });
        }

        if (! Schema::hasColumn('languages', 'active_for_translations')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->boolean('active_for_translations')->default(true)->after('active');
            });
        }

        if (! Schema::hasColumn('languages', 'sequence')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->unsignedInteger('sequence')->default(0)->after('active_for_translations');
            });
        }

        if (Schema::hasColumn('languages', 'translatable_sequence')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->dropColumn('translatable_sequence');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
