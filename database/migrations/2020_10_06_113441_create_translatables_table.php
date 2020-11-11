<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTranslatablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('translatables', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('source_field');
            $table->text('translated_value')->nullable()->default(null);
            $table->integer('language_id')->unsigned();
            $table->timestamps();

            $table->foreign('language_id')->references('id')->on('languages')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('translatables');
    }
}
