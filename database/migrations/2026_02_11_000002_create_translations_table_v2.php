<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('translations')) {
            $this->migrateExistingTable();

            return;
        }

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('group')->default('single');
            $table->string('key');
            $table->string('context')->nullable();
            $table->text('value')->nullable();
            $table->string('source')->default('manual');
            $table->boolean('is_locked')->default(false);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['language_id', 'group', 'key', 'context'], 'translation_unique');
            $table->index(['group', 'key']);
        });
    }

    protected function migrateExistingTable(): void
    {
        if (! Schema::hasColumn('translations', 'context')) {
            Schema::table('translations', function (Blueprint $table) {
                $table->string('context')->nullable()->after('key');
            });
        }

        if (! Schema::hasColumn('translations', 'source')) {
            Schema::table('translations', function (Blueprint $table) {
                $table->string('source')->default('manual')->after('value');
            });
        }

        if (! Schema::hasColumn('translations', 'is_locked')) {
            Schema::table('translations', function (Blueprint $table) {
                $table->boolean('is_locked')->default(false)->after('source');
            });
        }

        if (! Schema::hasColumn('translations', 'imported_at')) {
            Schema::table('translations', function (Blueprint $table) {
                $table->timestamp('imported_at')->nullable()->after('is_locked');
            });
        }

        $this->addUniqueIndexIfMissing();
    }

    protected function addUniqueIndexIfMissing(): void
    {
        $indexExists = collect(Schema::getIndexes('translations'))
            ->contains(fn ($index) => $index['name'] === 'translation_unique');

        if (! $indexExists) {
            try {
                Schema::table('translations', function (Blueprint $table) {
                    $table->unique(['language_id', 'group', 'key', 'context'], 'translation_unique');
                });
            } catch (\Exception $e) {
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
