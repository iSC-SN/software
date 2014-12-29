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

//responds to requests coming from GRA4 server
//passes through if it's not GRA4 requests, exits if it is
function GRA4Callback() {
//print_r($_SERVER);

    GRA4RfcHeadersToServerVars(); //see gra4_functions_web.php for comments

//let's make sure it's GRA4 request. if not - pass through
    if (!isset($_SERVER['HTTP_GRA4_CLIENT_SIGNATURE'])) {
        return; //go back to normal operations
    }

	while (ob_get_level()) 
	{
    	ob_end_clean();
    } 

//IMPORTANT ! - first we have to make sure we are called from GRA4, not from attackers
    $strRemoteSignature = GRA4GetServerVar('HTTP_GRA4_CLIENT_SIGNATURE');
    $strCurrentSecret = GRA4GetConfigValue('GRA4_secret');
    $strCurrentSignature = GRA4MakeSignature($strCurrentSecret);
    if ($strCurrentSignature !== $strRemoteSignature) { //server does not know our secret key
        die('GRA4:Auth Failed|' . $strRemoteSignature); //not going to talk to such a server !
    }
//if we here - SERVER KNOWS OUR SECRET KEY
    //GRA4_SET_SECRET
    //gra4 gives us new key. 
    if (isset($_SERVER['HTTP_GRA4_SET_SECRET'])) {
        $strSetSecret = GRA4GetServerVar('HTTP_GRA4_SET_SECRET');
        GRA4SetConfigValue('GRA4_secret', $strSetSecret);
        if ($strSetSecret != GRA4GetConfigValue('GRA4_secret')) { //verify
            die('GRA4:Unable to save configuration value GRA4_secret');
        }
    }

    //GRA4_SET_CLIENT_ID
    //gra4 gives us new client id. 
    if (isset($_SERVER['HTTP_GRA4_SET_CLIENT_ID'])) {
        $strSetClientId = GRA4GetServerVar('HTTP_GRA4_SET_CLIENT_ID');
        GRA4SetConfigValue('GRA4_client_id', $strSetClientId);
        if ($strSetClientId != GRA4GetConfigValue('GRA4_client_id')) { //verify
            die('GRA4:Unable to save configuration value GRA4_client_id');
        }
    }

//GRA4_SET_REMOTE_URL
//gra4 gives us new remote url.
    if (isset($_SERVER['HTTP_GRA4_SET_REMOTE_URL'])) {
        $strSetRemoteUrl = GRA4GetServerVar('HTTP_GRA4_SET_REMOTE_URL');
        GRA4SetConfigValue('GRA4_remote_url', $strSetRemoteUrl);
        if ($strSetRemoteUrl != GRA4GetConfigValue('GRA4_remote_url')) { //verify
            die('GRA4:Unable to save configuration value GRA4_remote_url');
        }
    }

//GRA4_FORWARD_MESSAGE
//forward message to the user - so they can be notified without visiting GRA4
    if (isset($_SERVER['HTTP_GRA4_FORWARD_MESSAGE'])) {
        $strToForwardUserId = GRA4GetServerVar('HTTP_GRA4_FORWARD_MESSAGE');
        $strSubject = 'EMPTY';
        $strBody = 'EMPTY';
        if(isset($_POST['GRA4_subject']))
        {
        	$strSubject = $_POST['GRA4_subject'];
    	}
        if(isset($_POST['GRA4_body']))
        {
	        $strBody = $_POST['GRA4_body'];
    	}
        GRA4ForwardMessageToLocalUser($strToForwardUserId, $strSubject, $strBody);
    }

//GRA4_CONFIRM_AUTH user privided password - is it correct?
    if (isset($_SERVER['HTTP_GRA4_CONFIRM_AUTH'])) {
        $strUserId = GRA4GetServerVar('HTTP_GRA4_CONFIRM_AUTH');
        $strPassword = 'EMPTY';
        if(isset($_POST['GRA4_password']))
        {
	        $strPassword = $_POST['GRA4_password'];
    	}
        if (!GRA4ConfirmLocalAuth($strUserId, $strPassword)) {
            die('GRA4:No'); //not good
        }
    }


//if we here - it was GRA4 request, and no error occured
    exit('GRA4:OK');
}

//EOF GRA4Callback
//NO EMPTY LINES AFTER '>', or Location header will fail !
?>