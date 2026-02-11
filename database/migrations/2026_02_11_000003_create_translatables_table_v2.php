<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('translatables')) {
            $this->migrateExistingTable();

            return;
        }

        Schema::create('translatables', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('field');
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->string('source')->default('manual');
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->unique(
                ['translatable_type', 'translatable_id', 'field', 'language_id'],
                'translatable_unique'
            );
        });
    }

    protected function migrateExistingTable(): void
    {
        if (Schema::hasColumn('translatables', 'source_field') && ! Schema::hasColumn('translatables', 'field')) {
            Schema::table('translatables', function (Blueprint $table) {
                $table->renameColumn('source_field', 'field');
            });
        }

        if (Schema::hasColumn('translatables', 'translated_value') && ! Schema::hasColumn('translatables', 'value')) {
            Schema::table('translatables', function (Blueprint $table) {
                $table->renameColumn('translated_value', 'value');
            });
        }

        if (! Schema::hasColumn('translatables', 'source')) {
            Schema::table('translatables', function (Blueprint $table) {
                $table->string('source')->default('manual')->after('value');
            });
        }

        if (! Schema::hasColumn('translatables', 'is_locked')) {
            Schema::table('translatables', function (Blueprint $table) {
                $table->boolean('is_locked')->default(false)->after('source');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('translatables');
    }
};
