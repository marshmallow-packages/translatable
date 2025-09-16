<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddActiveForTranslationToLanguagesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('languages') && !Schema::hasColumn('languages', 'active_for_translation')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->boolean('active_for_translation')->after('active')->nullable();
            });

            // Set active_for_translation to the current active value
            DB::table('languages')->update([
                'active_for_translation' => DB::raw('active')
            ]);

            // Make the column not nullable after prefilling
            Schema::table('languages', function (Blueprint $table) {
                $table->boolean('active_for_translation')->nullable(false)->default(true)->change();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('languages', 'active_for_translation')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->dropColumn('active_for_translation');
            });
        }
    }
}