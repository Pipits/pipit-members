<?php
    include_once '_version.php';

    $this->register_app('pipit_members', 'Pipit Members', 99, 'Utility app for Perch Members', PIPIT_MEMBERS_VERSION, true);
    $this->require_version('pipit_members', '3.0');
    
    
    spl_autoload_register(function($class_name){
        if (strpos($class_name, 'PipitMembers')===0) {
            include_once(PERCH_PATH.'/addons/apps/pipit_members/lib/'.$class_name.'.class.php');
            return true;
        }
        return false;
    });




    $current_page_link = rtrim( strtok($_SERVER["REQUEST_URI"],'?') , '/');
    if($current_page_link == PERCH_LOGINPATH.'/addons/apps/perch_members' && $CurrentUser->has_priv('pipit_members.members.export')) {
        // inject JS
        PerchUtil::mark($current_page_link);
        
        $API = new PerchAPI(1.0, 'pipit_members');
        $Perch = Perch::fetch();

        $js_path = '/js/app.js';
        $Perch->add_javascript( $API->app_path()."$js_path?v=" . filemtime(PerchUtil::file_path(PERCH_PATH.$API->app_nav().$js_path)) );
    }