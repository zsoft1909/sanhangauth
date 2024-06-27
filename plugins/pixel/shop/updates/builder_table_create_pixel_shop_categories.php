<?php namespace Pixel\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePixelShopCategories extends Migration
{
    public function up()
    {
        Schema::create('pixel_shop_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 180);
            $table->string('slug')->nullable();
            $table->string('image')->nullable();
            $table->string('description', 500)->nullable();

            $table->boolean('show_in_menu')->nullable();

            $table->string('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();

            $table->integer('parent_id')->nullable()->index()->default(0);
            $table->integer('nest_left')->nullable();
            $table->integer('nest_right')->nullable();
            $table->integer('nest_depth')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('pixel_shop_categories');
    }
}
