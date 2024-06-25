<?php namespace Zsoft\Base\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZsoftBaseContact extends Migration
{
    public function up()
    {
        Schema::create('zsoft_base_contact', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->smallInteger('type')->nullable()->default(1);
            $table->string('subject', 225)->nullable();
            $table->string('fullname', 225)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('email', 188)->nullable();
            $table->text('content')->nullable();
            $table->smallInteger('status')->nullable()->default(0)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zsoft_base_contact');
    }
}
