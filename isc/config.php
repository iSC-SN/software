<?php
if (!defined('IN_GRA4'))
{
	exit;
}
//TROF - check!
//Used during System first connect/ Do not change 
define('GRA4_INIT_REMOTE_URL', 'http://gate.gra4.com');


//For Debug only.  
//DO NOT UNCOMMENT -  all data of your GRA4 client may be overwritten
define('GRA4_FORCE_INIT', false);
//define('GRA4_FORCE_INIT', true);

//Strings 'GRA4_COMMA' and 'GRA4_EQUAL' are reserved for internal use.
//Please note - during automatic upgrade this file may be overwritten,
//so store a copy in a safe place, and reapply your changes after the upgrade.
global $aGRA4GlobalConfig;
if (!isset($aGRA4GlobalConfig)) 
{
	$aGRA4GlobalConfig = Array(
//For performance visualization turn to '1' 
// Adds text "Page created in..." on the bottom of a page
// Shows times for the local and remote processing
//	'show_times' => '1', 
//The URL for the user login of the client site.
// Used to offer guests to login to see the social functionality.
// GRA4 trys to detect this URL automatically, 
// but if you use custom login procedure, put real value and uncomment.
//   'login_url' => 'http://yourserver.com/system/login.php'
//Switches off service, so it does not compete with yours.
// See the website for complete list of values
// not recommended 
//	'disallow_services' => 'blogs,documents,files,bookmarks,likes',
//Includes extra CSS files right before social content,
// so you can redefine it's appearance.
// See /css/ folder of readme and examle
// You may include as many extra CSS files as you want,
// Note, the extra CSS files will be included in the
// order they mentioned here (not sorted by name),
// so the definitions in the end one will overwrite the previous ones.
	'include_css' => '/css/gra4.css,/css/custom.css',
//various operations, consult http://wiki.gra4.com
//	'extra' => 'UNLOCK',
	);//end of $aGRA4GlobalConfig
}//if

//NO EMPTY LINES AFTER '>', or Location headers will fail !
?>