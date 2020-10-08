<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Marshmallow\Translatable\Models\Language;

class CreateLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::create('languages', function (Blueprint $table) {
    		$table->increments('id');
            $table->string('name')->nullable();
            $table->string('language');
            $table->string('icon')->after('language')->default(null)->nullable();
            $table->timestamps();
    	});

        $initialLanguages = array_unique([
            config('app.fallback_locale'),
            config('app.locale'),
        ]);

        foreach ($initialLanguages as $language) {
            Language::firstOrCreate([
                'language' => $language,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('languages');
    }
}
