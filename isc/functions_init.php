<?php
/*------------------------------------------------------------------------
# GRA4 
# ------------------------------------------------------------------------
# author    gra4.com
# copyright Copyright (C) 2012 gra4.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://gra4.com
# Technical Support:  http://gra4.com/groups/profile/98/gra4-webmasters-wordpress
-------------------------------------------------------------------------*/

//called only once, when the client system commects to rga4
if (!defined('IN_GRA4')) {//make sure it can be called only from the php, not directly over the web
    exit;
} 

//if returns not empty string - it's error message
function GRA4CreateInitNewClientData(&$strInitData) {
    global $aGRA4GlobalConfig;

    GRA4SetConfigValue('GRA4_remote_url', GRA4_INIT_REMOTE_URL); //were we navigate by default
//only admin of the local system can register new gra4 client
    if (!GRA4IsLocalAdmin()) {
        return("ERROR: Please login as Administrator of your system and return to this URL.");
    }
//If we here - the admin of the future rga4 client is using the script. We ok.	
//let's make sure callback mentioned in the aGRA4GlobalConfig matches actual callback file name
    /* TROF - remove	
      $strConfigCallback = $aGRA4GlobalConfig['callback_name'];
      if(!file_exists(dirname(__FILE__).'/callback/'. $strConfigCallback))
      {
      $strCallBackUrl = $strFullClientUrl."/callback/".$strConfigCallback;
      $strFullClientUrl = GRA4CreateClientUrl();
      return("ERROR: Congifuration field 'callback_name' does not match actual callback script. <br> Make sure <a target=_new href='$strCallBackUrl' >$strCallBackUrl</a> exists !");
      }
     */
//if we here - configuration is ok	
//we create temporary secret key, it will be replaced with the real one from the gra4
// Server will full the callback file with the command "set key"
// 	We will be sure the call is comming from gra4 by verifying the signatire of the temporary key
    $strTempSecret = 'TMP' . md5(rand());
    GRA4SetConfigValue('GRA4_secret', $strTempSecret);
    $strCurrentSecret = GRA4GetConfigValue('GRA4_secret');
//die("$strCurrentSecret|$strTempSecret");	
    if ($strCurrentSecret !== $strTempSecret) {
        return('ERROR: Unable to create temporary Secret Key'); //something terrible happened
    }

    $strSiteName = GRA4GetLocalSiteName();
    $strInitData =
            "GRA4_INIT: $strTempSecret\r\n" . //one space in front, no trailing spaces!
            "GRA4_CLIENT_SITENAME: $strSiteName\r\n";
    return ''; //no error
}



//NO EMPTY LINES AFTER '>', or Location header will fail !
?>