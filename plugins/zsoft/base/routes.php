<?php

use Backend\Facades\BackendAuth;
use Cms\Models\MaintenanceSetting;

App::before(function($request){

	$maintenance = MaintenanceSetting::get('is_enabled');
 	$backendPrefix = str_replace('/', '', Config::get('cms.backendUri', 'backend'));
 	if(!Request::is($backendPrefix.'/*') && !Request::is($backendPrefix)){
 		if($maintenance == TRUE && !BackendAuth::check()){
 			return Redirect::to($maintenance);
		}
	}

});