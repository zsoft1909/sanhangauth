<?php namespace Zsoft\Base\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZsoftBaseComments extends Migration
{
    public function up()
    {
        Schema::create('zsoft_base_comments', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('moduleid');
            $table->string('module', 10)->default('blog');
            $table->integer('userid');
            $table->string('name', 225)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 100)->nullable();
            $table->smallInteger('rating')->nullable()->default(0);
            $table->text('content')->nullable();
            $table->smallInteger('status')->nullable()->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zsoft_base_comments');
    }
}
