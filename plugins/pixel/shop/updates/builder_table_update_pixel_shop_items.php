<?php namespace Pixel\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePixelShopItems extends Migration
{
    public function up()
    {
        Schema::table('pixel_shop_items', function($table)
        {
            $table->renameColumn('image', 'image_default');
        });
    }
    
    public function down()
    {
        Schema::table('pixel_shop_items', function($table)
        {
            $table->renameColumn('image_default', 'image');
        });
    }
}
