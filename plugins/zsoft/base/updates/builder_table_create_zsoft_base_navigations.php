<?php namespace Zsoft\Base\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZsoftBaseNavigations extends Migration
{
    public function up()
    {
        Schema::create('zsoft_base_navigations', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('position')->index();
            $table->integer('parent_id')->unsigned()->index();
            $table->string('name', 225)->nullable();
            $table->string('target', 225)->nullable();
            $table->string('active_code', 50)->nullable();
            $table->smallInteger('status')->nullable()->default(1);
            $table->integer('nest_left')->nullable()->index();
            $table->integer('nest_right')->nullable()->index();
            $table->integer('nest_depth')->nullable()->index();
            $table->integer('user_id')->nullable()->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zsoft_base_navigations');
    }
}
