<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTranslatableSequenceToLanguagesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('languages') && !Schema::hasColumn('languages', 'translatable_sequence')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->integer('translatable_sequence')->after('active')->nullable();
            });

            // Prefill with existing ID values to maintain current sequence
            DB::table('languages')->update([
                'translatable_sequence' => DB::raw('id')
            ]);

            // Make the column not nullable after prefilling
            Schema::table('languages', function (Blueprint $table) {
                $table->integer('translatable_sequence')->nullable(false)->change();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('languages', 'translatable_sequence')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->dropColumn('translatable_sequence');
            });
        }
    }
}
