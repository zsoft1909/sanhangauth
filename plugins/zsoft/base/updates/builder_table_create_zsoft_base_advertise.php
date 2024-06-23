<?php namespace Zsoft\Base\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZsoftBaseAdvertise extends Migration
{
    public function up()
    {
        Schema::create('zsoft_base_advertise', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('position')->index();
            $table->string('name', 100);
            $table->string('image', 225)->nullable();
            $table->string('target', 225)->nullable();
            $table->smallInteger('status')->default(1)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zsoft_base_advertise');
    }
}
