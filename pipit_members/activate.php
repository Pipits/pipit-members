<?php
    $API = new PerchAPI(1.0, 'pipit_members');
    $UserPrivileges = $API->get('UserPrivileges');
    $UserPrivileges->create_privilege('pipit_members.members.export', 'Export members details from the Perch Members app');

    $Settings = $API->get('Settings');
	$Settings->set('pipit_members_version', PIPIT_MEMBERS_VERSION);