<?php
//interface between gra4 and local client system
if (!defined('IN_GRA4'))
{
	exit;
}

//must be absolute path - called from different locations, including callback script 
$strPathToMicroCommon = mb_strrichr(dirname(__FILE__),'/',true) . '/common.php';
include_once($strPathToMicroCommon);


function GRA4GetLocalClientVersion()
{
	return('micro01.0.0.0.8');
//PLEASE NOTE: This is version of GRA4 interface, not the version of this module!
//If you building new GRA4 clien for some popular engine, use name_of_engine.0.1.0.1
//For support contact GRA4 at http://gra4.com/relatedgroups/owner/94
}

//IMPORTANT - if  GRA4IsAuthenticatedUser() function is incorrect, 
//the anonimous users will be able to pretend they are authenticated by your site
function GRA4IsAuthenticatedUser()
{
	return ( (isset($_SESSION['userName'])) && (isset($_SESSION['userId'])) && (isset($_SESSION['validUser'])) ); 
}

function GRA4GetLocalUserName()
{
	$strName = trim(mb_strrichr($_SESSION['userName'],'@',true)); //don't send whole email even to GRA4
	if($strName == '')//should never happen with normal email addresses !
	{
		$strName = trim($_SESSION['userName']);
	}
	if($strName == '')//still ?! This is wierd! 
	{
		$strName = $_SESSION['userId']; //must be present
	}
	return ($strName); 
}

function GRA4GetLocalUserId()
{
	return ($_SESSION['userId']); //not a secret information - can be obtained just by browsing the website
}


function GRA4GetLocalSiteName()
{
	return (getSiteTitle());  //not a secret information - can be obtained just by browsing the website
}

function GRA4IsLocalAdmin()
{
	return (userIsAdmin());
}


//forward message to the user - so they can be notified without visiting GRA4
//NOTE - function is declared as anonimous, so it can be redifined in /mod/
// see http://www.php.net/manual/en/functions.anonymous.php
function GRA4ForwardMessageToLocalUser ($strUserId,$strSubject,$strBody)
{
	if(get_magic_quotes_gpc() == 1)	
	{
		$strSubject = stripslashes($strSubject);
		$strBody = stripslashes($strBody);
	}
	$strSubject = '[GRA4] '. html_entity_decode($strSubject);
	$strBody = html_entity_decode($strBody);
	
	
	$aUsers = getUsers(); //id:email:password:status
  for($i = 0; $i < count($aUsers); $i++) 		// Check user existance
  {
  	$tmp = explode(':', $aUsers[$i]);
  	if($tmp[0] == $strUserId)
		{
			$bRes = mail($tmp[1],$strSubject,$strBody, 
			"MIME-Version: 1.0\r\n".
			"Content-Type: text/plain; charset=utf-8 \r\n"	);
			
			return;
    }
	}//for
}; //EOF GRA4ForwardMessageToLocalUser()
//don't forget ';' in the end!


//NOTE - GRA4 does not ask (and never will ask) for password of the local user.
//GRA4 just wants to confirm that the password user willingly provided is valid.
function GRA4ConfirmLocalAuth($strUserId, $strPassword)
{
	$aUsers = getUsers(); //id:email:password:status
  for($i = 0; $i < count($aUsers); $i++)
  {
  	$aOne = explode(':', $aUsers[$i]);
  	if( ($aOne[0] == $strUserId) && (md5($strPassword) == $aOne[2]) )
  	{
			return true; //found ! we done  	
  	}
	}//for
	return false; 
}

//This function is pretty much for the development of GRA4 modules for new platforms
//Insert it somewhere where the GRA4 functionality must be alreary present, and see if it is actually present =)
function GRA4TestLocalFunctions()
{
//TEST OF LOCAL FUNCTIONS
	echo('<br>function GRA4GetLocalClientVersion()<br>');
	echo('Local Client Version:' . GRA4GetLocalClientVersion());

	echo('<hr>function GRA4IsAuthenticatedUser()<br>');
	if(GRA4IsAuthenticatedUser()) echo('In Authenticated User ');
	else echo('In NOT Authenticated User ');


	echo('<hr>function GRA4GetLocalUserName()<br>');
	echo('Local User Name:' . GRA4GetLocalUserName());

	echo('<hr>function GRA4GetLocalUserId()<br>');
	echo('Local User Id:' . GRA4GetLocalUserId());

	echo('<hr>function GRA4GetLocalSiteName()<br>');
	echo('Local Site Name:' . GRA4GetLocalSiteName());


	echo('<hr>function GRA4IsLocalAdmin()<br>');
	if(GRA4IsLocalAdmin()) echo('In Local Admin');
	else echo('In NOT In Local Admin');


	echo('<hr>function GRA4ForwardMessageToLocalUser ($strUserId,$strSubject,$strBody)<br>');
	if(GRA4IsAuthenticatedUser())
	{
		GRA4ForwardMessageToLocalUser(GRA4GetLocalUserId(),'Test Message from GRA4TestLocalFunctions',"Hello\nLine One\nLine Two");
		echo('Message is on the way - check if you received it');
	}
	else
	{
		echo('Unable to test - local user is not logged in');
	}


	echo('<hr>function GRA4ConfirmLocalAuth($strUserId, $strPassword)<br>');
	$strMyPassword = 'ActuallPassword'; //Don't forget to erase after test
	if(GRA4IsAuthenticatedUser())
	{
		echo('Auth for User Id:' . GRA4GetLocalUserId() . ' and password:' . $strMyPassword);

		if(GRA4ConfirmLocalAuth(GRA4GetLocalUserId(), $strMyPassword)) echo(' is confirmed');
		else echo(' is NOT confirmed');
	}
	else
	{
		echo('Unable to test - local user is not logged in');
	}
}//EOF GRA4TestLocalFunctions

//NO EMPTY LINES AFTER '>', or Location headers will fail !
?>