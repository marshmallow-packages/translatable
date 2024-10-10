<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMissingTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('missing_translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('missing_translatable', 'missing_translatable');
            $table->json('missing');
            $table->integer('language_id')->unsigned();
            $table->timestamps();
        });

        if (Schema::hasTable('languages')) {
            Schema::create('missing_translations', function (Blueprint $table) {
                $table->foreign('language_id')->references('id')->on('languages')->onDelete('CASCADE');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('missing_translations');
    }
}
