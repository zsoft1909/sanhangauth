<?php namespace Zsoft\Base\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Zsoft\Base\Models\Setting;

class BuilderTableCreateZsoftStoreSetting extends Migration
{
    public function up()
    {
        Schema::create('zsoft_base_setting', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('company_name', 225)->nullable();
            $table->text('company_desc')->nullable();
            $table->string('address', 225)->nullable();
            $table->string('phone', 150)->nullable();
            $table->string('hotline', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('email_contact', 225)->nullable();
            $table->string('website', 150)->nullable();
            $table->string('slogan', 225)->nullable();
            $table->string('logo', 225)->nullable();
            $table->string('favicon', 225)->nullable();
            $table->string('facebook', 225)->nullable();
            $table->string('youtube', 225)->nullable();
            $table->string('tiktok', 225)->nullable();
            $table->string('instagram', 225)->nullable();
            $table->string('title_seo', 225)->nullable();
            $table->string('keyword_seo', 225)->nullable();
            $table->string('description_seo', 225)->nullable();
            $table->string('image_share', 225)->nullable();
            $table->text('script_head')->nullable();
            $table->text('script_body')->nullable();
            $table->string('fb_admin', 100)->nullable();
            $table->string('fb_app', 100)->nullable();
            $table->text('promo')->nullable();
            $table->text('extras')->nullable();
        });

        Setting::create([
            'company_name' => 'Zsoft.vn',
            'company_desc' => '',
            'address' => '',
            'phone' => '0989.999.999',
            'hotline' => '0989.999.9991',
            'email' => 'hotro@zsoft.vn',
            'website' => 'https://zsoft.vn'
        ]);
    }
    
    public function down()
    {
        Schema::dropIfExists('zsoft_base_setting');
    }
}
