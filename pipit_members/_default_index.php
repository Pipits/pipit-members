<?php
    # include the API and classes
    include(__DIR__.'/../../../core/inc/api.php');
    
    spl_autoload_register(function($class_name){
        if (strpos($class_name, 'PipitMembers')===0) {
            include_once(PERCH_PATH.'/addons/apps/pipit_members/lib/'.$class_name.'.class.php');
            return true;
        }
        return false;
    });


    foreach($privs as $priv) {
		if ( !$CurrentUser->has_priv($priv) ) PerchSystem::redirect( PERCH_LOGINPATH );
	}

    $API  = new PerchAPI(1.0, 'pipit_members');
	$Lang = $API->get('Lang');
	$HTML = $API->get('HTML');
	$Paging = $API->get('Paging');
	$Template = $API->get('Template');

    include('modes/_subnav.php');
	include('modes/'.$mode.'.pre.php');

	# Top layout
	include(PERCH_CORE . '/inc/top.php');

	# Display your page
	include('modes/'.$mode.'.post.php');

	# Bottom layout
	include(PERCH_CORE . '/inc/btm.php');