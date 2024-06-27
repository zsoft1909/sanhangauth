<?php namespace Pixel\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePixelShopItems2 extends Migration
{
    public function up()
    {
        Schema::table('pixel_shop_items', function($table)
        {
            $table->string('image_hover', 225)->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('pixel_shop_items', function($table)
        {
            $table->dropColumn('image_hover');
        });
    }
}
