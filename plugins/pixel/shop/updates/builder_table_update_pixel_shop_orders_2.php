<?php namespace Pixel\Shop\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdatePixelShopOrders2 extends Migration
{
    public function up()
    {
        Schema::table('pixel_shop_orders', function($table)
        {
            $table->string('customer_name', 225);
            $table->string('customer_address', 225)->nullable();
            $table->dropColumn('customer_first_name');
            $table->dropColumn('customer_last_name');
        });
    }
    
    public function down()
    {
        Schema::table('pixel_shop_orders', function($table)
        {
            $table->dropColumn('customer_name');
            $table->dropColumn('customer_address');
            $table->string('customer_first_name', 60);
            $table->string('customer_last_name', 60)->nullable();
        });
    }
}
