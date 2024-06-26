<?php namespace Zsoft\Base\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZsoftBaseSlider extends Migration
{
    public function up()
    {
        Schema::create('zsoft_base_slider', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 225)->nullable();
            $table->string('image', 225)->nullable();
            $table->string('target', 225)->nullable();
            $table->smallInteger('status')->nullable()->index()->default(1);
            $table->integer('sort_order')->nullable()->index()->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zsoft_base_slider');
    }
}
