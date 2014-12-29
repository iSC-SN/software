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

// main functionality of the gra4 client
if (!defined('IN_GRA4')) {//make sure it can be called only from the php, not directly over the web
    exit;
}

if(!function_exists('mb_strlen'))
{
	return('The sbstring extension library is not available. Please adjust your PHP configuration');
}

include_once('gra4_functions_local.php');
//if we here - security is ok, we are called from the script, not directly from the web

include_once('gra4_config.php'); //current dir
include_once('gra4_functions_web.php'); // GRA4InitNewClientSiteis there
include_once('gra4_functions_callback.php'); // call last! GRA4Callback there

GRA4Callback();

//ATT! We never ever send out secret key !
// (except once during the registation in the new gra4 client)
// We send the signature, derived from secret key.
// If incoming signature match signature we generated here, 
// meaning the other side knows the secret key, so we can trust it  
// SH-1 (secure hash algorithm) is strong enough for now,
// Read more at http://en.wikipedia.org/wiki/SHA-1
// SH-1 but may be replaced in the next versions of gra4,
// so make sure you always send out version of the client
function GRA4MakeSignature($strSecretKey) {
    return sha1($strSecretKey);
}

//EOF GRA4MakeSignature
//this function will help to create trace of the script. 
function GRA4Log($strLabel, $strBody, $strFileName = "GRA4log.txt") {
//comment line bellow for trace. Remeber, log file grows pretty fast!
//	return true; //write nothing, exit function
    $f = @fopen($strFileName, "a"); //append
    if (!$f) {
        return false; //ok, we are screwed big time - can't open file
    } else {
        fwrite($f, "\n" . posix_getpid() . " " . GRA4GetServerVar('REMOTE_ADDR') . " " . date(DATE_RFC822) . "\n$strLabel\n\t$strBody");
        fclose($f);
        return true;
    }
}

//EOF GRA4Log
//For security reasons filters out all cookies do not belong to the social network
// GRA4 runs inside the client software, so we make sure we do not pass any cooikes  
// not belonging to GRA4 to the server to preserve the client data. 
function GRA4FilterCookies($strAllCookies) { //
    $aAllCookies = explode('; ', $strAllCookies);
    $aFilteredCookies = array_filter($aAllCookies, 'GRA4IsOurCookie');
    $strFilteredCookies = implode('; ', $aFilteredCookies);
    return $strFilteredCookies;
}

//used for array_filter in FilterCookies
function GRA4IsOurCookie($strCookie) { //callback for array_filter
    if (mb_strpos($strCookie, 'GRA4_') === 0) {
        return 1; //our cookie, going to pass to the GRA4
    }
    return 0; //not our, can't touch it
}

function GRA4CreateConfigLine() { //prepare config values to send out
    global $aGRA4GlobalConfig;
    $sConfigLine = '';
    $aKeys = array_keys($aGRA4GlobalConfig);
    $aValues = array_values($aGRA4GlobalConfig);
    for ($i = 0; $i < count($aGRA4GlobalConfig); $i++)
	{
		if($aKeys[$i] == 'include_css') //going to filter out non-existing css files
	    {
		    $aFiles = explode(',',$aValues[$i]);
		    $sFiles = '';
		    for($j=0; $j < count($aFiles); $j++)
		    {
				if(file_exists(dirname(__FILE__).$aFiles[$j])) 
				{
					if(mb_strlen($sFiles) == 0)	
					{
						$sFiles = $aFiles[$j];
					}
					else
					{
						$sFiles .= ','.$aFiles[$j];
					}
				}   
		   	}
		   	$aValues[$i] = $sFiles;
		}
		$aValues[$i] = str_replace(array(',','='),array('GRA4_COMMA','GRA4_EQUAL'),$aValues[$i]);		
        $sConfigLine .= $aKeys[$i] . '=' . $aValues[$i] . ';';
    }
    return $sConfigLine;
}//EOF  GRA4CreateConfigLine

function GRA4CreateClientUrl() {
	
	$strShortSelf = GRA4CreateStrShortSelf();
	
// OLD    $strShortSelf = str_replace(mb_strrchr(GRA4GetServerVar('PHP_SELF'), '/'), '', GRA4GetServerVar('PHP_SELF'));

//TODO we may need to change when we  run on https
    $strProtocol = 'http';     
	$strFullClientUrl = $strProtocol . "://" . GRA4GetServerVar('HTTP_HOST') . $strShortSelf . '/gra4';
	
    return $strFullClientUrl;
}
//EOF GRA4CreateClientUrl

function GRA4CreateStrShortSelf() {
    $baseCMSPath = str_replace( mb_strrchr(GRA4GetServerVar('PHP_SELF'), '/'), '', GRA4GetServerVar('PHP_SELF'));
    $baseCMSPath = str_replace(mb_strcut( $baseCMSPath, mb_strpos($baseCMSPath, '/gra4')), '', $baseCMSPath);
	
	$menuPath = mb_substr(GRA4GetServerVar('REQUEST_URI'), mb_strlen( $baseCMSPath) );
	$menuPath = str_replace(mb_strcut( $menuPath, mb_strpos($menuPath, '/gra4')), '', $menuPath );
	
	$strShortSelf = $baseCMSPath.$menuPath;
	return $strShortSelf;
}


function GRA4FetchRemoteContent(&$bFrameNeeded) {

    global $aGRA4GlobalConfig; //defined in gra4_config.php
    $bFrameNeeded = true;

    $starttimer1 = time() + microtime(); //we need it if config  show_times=1

    $strContentToReturn = ''; //we goung to return this one

    $strSecret = GRA4GetConfigValue('GRA4_secret');

    $strInitData = '';
    if ((empty($strSecret)) || (mb_strpos($strSecret, 'TMP') === 0) || (GRA4_FORCE_INIT === true)) { // === , or FALSE will screw it!
        include_once('gra4_functions_init.php'); // GRA4InitNewClientSiteis there
        $strErrorMessage = GRA4CreateInitNewClientData($strInitData);

        if (mb_strlen($strErrorMessage) > 0) { //we've got error message - something went wrong
            return $strErrorMessage; //we done - show message and die
        } else { // prepared for init with no errors. Secret may be reseted though
            $strSecret = GRA4GetConfigValue('GRA4_secret'); //so we grab it again just in case
        }
    }
//only here - may be set by GRA4CreateInitNewClientHeaders	
    $strRemoteUrl = GRA4GetConfigValue('GRA4_remote_url');

//if we here, the client has been initialized already or prepared to do so
//ATT! WE never send secret key itself over the Net (secret key suppose to be secret)
// we send signature insted. here goes: 
    $strSignature = GRA4MakeSignature($strSecret);
    $strLocalUserData = '';
    if (GRA4IsAuthenticatedUser() === true) { //ONLY if authenticated user! 
        $strUserName = GRA4GetLocalUserName(); //not a secret information - can be obtained just by browsing the website
        $strUserId = GRA4GetLocalUserId(); //not a secret information - can be obtained just by browsing the website
        $strLocalUserData = "GRA4_CLIENT_USERNAME: $strUserName\r\n" . //we use it as a nickname, can be changed in the profile
                "GRA4_CLIENT_USERID: $strUserId\r\n";     //we use ID to create social network id, Id can't be changed, username can.           
    }

// GRA4GetServerVar('PHP_SELF') like /phpBB3/gra4/index.php
// $strShortSelf like /phpBB3/gra4 

// OLD    $strShortSelf = mb_substr(GRA4GetServerVar('PHP_SELF'), 0, mb_strlen(GRA4GetServerVar('PHP_SELF')) - mb_strlen(mb_strrchr(GRA4GetServerVar('PHP_SELF'), '/')) );

	$strShortSelf = GRA4CreateStrShortSelf();

//WARNING we may need to change when we  run on https
    $strFullClientUrl = GRA4CreateClientUrl();
	
    $strParams = mb_substr(GRA4GetServerVar('REQUEST_URI'), mb_strlen($strShortSelf));
	$strParams = str_replace( '.html', '', $strParams); // if need, delete 
    $strParams = mb_substr($strParams, mb_strlen('gra4/')); // skip "gra4" joomla component 

    $strUrl = trim($strRemoteUrl).$strParams; //trim just in case of mismatch '\r\n'
	
    $strClientVersion = GRA4GetLocalClientVersion();

    $strUserAgent = GRA4GetServerVar('HTTP_USER_AGENT');
    $strClientCookies = GRA4FilterCookies(GRA4GetServerVar('HTTP_COOKIE')); //MUST pass to the server, so it holds session
    $strReferer = GRA4GetServerVar('HTTP_REFERER');
	
    $strLanguage = GRA4GetServerVar('HTTP_ACCEPT_LANGUAGE');
    $strAccept = GRA4GetServerVar('HTTP_ACCEPT');
    $strIP = GRA4GetServerVar('REMOTE_ADDR');

	$strClientId = GRA4GetConfigValue('GRA4_client_id');
//prepare config values to send out
    $sConfigLine = GRA4CreateConfigLine();
    

    $strHeaders = "Cookie: $strClientCookies\r\n" .
            "Referer: $strReferer\r\n" . //gra4 needs it to operate correctly
            "User-Agent: $strUserAgent\r\n" .
            "Accept-Language: $strLanguage\r\n" . //to tune-up for international user
            "Accept: $strAccept\r\n" . //to tune-up for international user
            "GRA4_CLIENT_VERSION: $strClientVersion\r\n" . //so gra4  knows how to process the info
            "GRA4_CLIENT_URL: $strFullClientUrl\r\n" .
			"GRA4_CLIENT_ID: $strClientId\r\n" .
			"GRA4_CLIENT_REMOTE_ADDR: $strIP\r\n" . //you may comment this line(not required), but visitor IPs help GRA4 to block spambots
            "GRA4_CLIENT_CONFIG: $sConfigLine \r\n" . //extra parameters for gra4            
            "GRA4_CLIENT_SIGNATURE: $strSignature\r\n" . //one space in front, no trailing spaces!
            $strLocalUserData .
            $strInitData;

    $aResultHeaders = Array();
    $strContentToReturn = GRA4FetchWebData($strUrl, $strHeaders, $aResultHeaders);
	
    $strContentLengthHeader = false;
    $strContentEncodingHeader = false;
    for ($i = 0; $i < count($aResultHeaders); $i++) {
        if (mb_strpos($aResultHeaders[$i], 'GRA4_') === 0) {
            $aResultHeaders[$i] = mb_substr($aResultHeaders[$i], mb_strlen('GRA4_'));
        }
//
        if (((mb_strpos($aResultHeaders[$i], 'Content-Type: ') === 0) && (mb_strripos($aResultHeaders[$i], 'text/html') === false)) || (mb_strpos($aResultHeaders[$i], 'NoFrame: ') === 0)) {
            $bFrameNeeded = false;
        }

        if (mb_strpos($aResultHeaders[$i], 'Content-Encoding: ') === 0) {
            $strContentEncodingHeader = $aResultHeaders[$i];
            continue; //not going to send it now - if we have frame, we can't decide is content zipped or not for example
        }

        if (mb_strpos($aResultHeaders[$i], 'Content-Length: ') === 0) {
            $strContentLengthHeader = $aResultHeaders[$i];
            continue; //not going to send it now - if we have frame, length of whole page is different
        }
        header($aResultHeaders[$i], true);
    }//end loop
//if it's binary content, some browsers require Content-Length
    if (($bFrameNeeded === false) && ($strContentLengthHeader !== false)) {
//NOT GOING TO SEND - it's optional header
// the received length may be wrong because of gzip. Too lazy to recalculate.	
//		header($strContentLengthHeader,true);	
    }
    if (($bFrameNeeded === false) && ($strContentEncodingHeader !== false)) {
//TODO implement encoding later
//		header($strContentEncodingHeader,true);	
    }




    if ( ((isset($aGRA4GlobalConfig['show_times'])) && ($aGRA4GlobalConfig['show_times'] === '1')) && ($bFrameNeeded === true)) {
        $stoptimer1 = time() + microtime();
        $timer1 = round($stoptimer1 - $starttimer1, 4);
        $strContentToReturn .= "<div class=\"gra4_stats\">Local Page created in $timer1 s.</div>";
    }
	
    return $strContentToReturn;
}

//we use flat file so we are not depending on the system (simplify development for other systems)
$aGra4Config = Array(); //static array


function GRA4GetConfigValue($strVarName) {
    global $aGra4Config;
    if (count($aGra4Config) == 0) { //empty
        $strFileName = dirname(__FILE__) . '/gra4.cfg';
        if (file_exists($strFileName)) {
            $strData = file_get_contents($strFileName);
//            $aGra4Config = explode("\r\n", $strData);
            $aGra4Config = explode("\n", $strData);
        }
    }
    for ($i = 0; $i < count($aGra4Config); $i++) {
        $aNameValue = explode('=', $aGra4Config[$i]);
        if ($aNameValue[0] == $strVarName) {
            return $aNameValue[1];
        }//if found
    }//for loop
    return ''; //pair name/value not found
}//GRA4GetConfigValue


function GRA4SetConfigValue($strVarName, $strVarValue) {
    global $aGra4Config;

    if (count($aGra4Config) == 0) { //empty
        $strFileName = dirname(__FILE__) . '/gra4.cfg';
        if (file_exists($strFileName)) {
            $strData = file_get_contents($strFileName);
            $aGra4Config = explode("\n", $strData);
//            $aGra4Config = explode("\r\n", $strData);
        }
    }

    $bKeyExists = false;
    $strPair = $strVarName . '=' . $strVarValue;
    for ($i = 0; $i < count($aGra4Config); $i++) {
        $aNameValue = explode('=', $aGra4Config[$i]);
        if ($aNameValue[0] == $strVarName) {
            $aGra4Config[$i] = $strPair;
            $bKeyExists = true;
            break;
        }//if found
    }

    if ($bKeyExists === false) {
        $aGra4Config[$i] = $strPair; 
    }

    $strDirName = dirname(__FILE__) ;
    $strFileName = dirname(__FILE__) . '/gra4.cfg';
    if(is_writable( $strDirName ) === false) //we have to be able to write there. we can't
    { 
		if( !@chmod($strDirName, 0770))
		{ //let's try to make it writeble
 			trigger_error("FATAL ERROR: Unable to write to '$strFileName' to save the configuration.  You have to change the permissions for '$strDirName' manually.",E_USER_ERROR); //ok we did whatever we could to be able to write the config
			//accordingly to http://php.net/manual/en/errorfunc.constants.php Execution of the script is halted.
		}
    }//if not writeble

    
//    if(file_put_contents($strFileName, implode("\r\n", $aGra4Config)) === false) 
    if(file_put_contents($strFileName, implode("\n", $aGra4Config)) === false) 
    {
 		trigger_error("FATAL ERROR: Unable to save configuration to '$strFileName' . You have to change the permissions manually.",E_USER_ERROR); //ok we did whatever we could to be able to write the config
		//accordingly to http://php.net/manual/en/errorfunc.constants.php Execution of the script is halted.
    }
    else // wrote ok
    {
//        if( !@chmod($strFileName, 0770))//lock it. we write there not too often, so it's ok to do it every time
//		{
// 			echo "Unable to protect  '$strFileName' from web access.<br>  
//			You have to change the permissions manually.<br>
//			PLEASE RELOAD CURRENT PAGE NOW.";
//		}
    }

}//EOF GRA4SetConfigValue


//Specific for 'subdir' systems, like phpBB or osCommerce (popular, so we keep it in the core)
//Creates .htaccess file with correct RewriteBase. 
//Used once during the installation process.
//If we error out here, installation has failed.
//Returns error message, or true on success
function GRA4HtaccessWriter($bOnlyIfDoesNotExist = true)
{
	$strRealFileName = dirname(__FILE__).'/.htaccess'; //like '/home/user123/public_html/donain123/somesystem/gra4/.htaccess'
	if(file_exists($strRealFileName) && ($bOnlyIfDoesNotExist == true) )
	{
		return true; // we done - .htaccess exists, nothing to do	
	}
//let's find htaccess.txt, later we will modify it and save as .htaccess
	$strTemplateFileName = dirname(__FILE__).'/htaccess.txt'; //like '/home/user123/public_html/donain123/somesystem/gra4/htaccess.txt'
	
	if(!file_exists($strTemplateFileName))
	{
		return("The installation is missing ".$strTemplateFileName);
	}
	$strSelfName = GRA4GetServerVar('SCRIPT_NAME'); //like '/somesystem/gra4/somescript.php'
	$strNewRewriteBaseValue = 'RewriteBase ' . mb_strrchr($strSelfName,'/',true); //like 'RewriteBase /somesystem/gra4' - what we need

	$strOldRewriteBaseValue = 'RewriteBase /'; //going to find it ang change to the new one

	$strTemplateFileContents = file_get_contents($strTemplateFileName);

	if($strTemplateFileContents === false)
	{
		return("Unable to read ".$strTemplateFileName." - check permissions.");
	}
	
	$iChanges = 0; //just to declare
	$strRealFileContents = str_replace($strOldRewriteBaseValue, $strNewRewriteBaseValue, $strTemplateFileContents,$iChanges);

	if($iChanges !== 1) //must be only one. 
	{
		return($strTemplateFileName." is corrupted");
	}

	if(file_exists($strRealFileName))
	{
		if(unlink($strRealFileName) === false)
		return("The installation missing ".$strTemplateFileName);
	}

	$bWriteResult = file_put_contents($strRealFileName,$strRealFileContents);

	if($bWriteResult === false)
	{
		return("Unable to write ".$strRealFileName." - check permissions.");
	}

	return true; //if we here - we ok =)

}//EOF GRA4HtaccessWriter



function GRA4GetServerVar($strIndexName) //so we don't have 'undefined index' in error logs
{
	if(isset($_SERVER[$strIndexName]))
	{
		return($_SERVER[$strIndexName]);
	}	
	return('');
}

function gra4_is_private_ip($ip)
{
	// If IP private, return FALSE.
  return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
}
//NO EMPTY LINES AFTER '>', or Location header will fail !
?>