<?php namespace Zsoft\Base;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Zsoft\Base\Components\Setting'  => 'setting',
            'Zsoft\Base\Components\Comment'  => 'comment',
            'Zsoft\Base\Components\Contact'  => 'contact'
        ];
    }

    public function registerSettings()
    {
        
    }

    public function registerMarkupTags(){
        $twig = $this->app->make('twig.environment');
        $twigDate = "twig_date_format_filter";

        return [
            'filters' => [
                'ldate' => function($date, $format = null, $timezone = null ) use ($twig, $twigDate) {
                    $date = \October\Rain\Argon\Argon::parse($date);
                    return $twigDate($twig, $date, $format, $timezone);
                },  
            ],  
        ];  
    }
}
