<?php
require_once('language.php');
session_start();
$bUserJustConfirmed = false;

// print_r($_SESSION); print_r(mb_strrichr($_SESSION['userName'],'@',true)); print_r($_SERVER);

function error_handler($level, $message, $file, $line, $context) {
    //Handle user errors, warnings, and notices ourself
//die("|$level|$message|$file|$line|$context|");    
    if($level === E_USER_ERROR || $level === E_USER_WARNING || $level === E_USER_NOTICE) 
	{
        echo '<strong>'.$message.'</strong> ';
        return(true); //And prevent the PHP error handler from continuing
    }
    return(false); //Otherwise, use PHP's error handler
}
set_error_handler('error_handler');//set it right here, in the include
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING); //supress light stuff


function registerUser($user,$pass1,$pass2){
	global $language;
	global $bUserJustConfirmed ;
	$errorText = '';
	// Check passwords
	if ($pass1 != $pass2) $errorText = $language['passwords_not_mach'];
	elseif (filter_var($user, FILTER_VALIDATE_EMAIL) === false ) $errorText = $language['email_bad'];
	elseif (mb_strlen($pass1) < mb_strlen('****')) $errorText = $language['password_short'];
	elseif ((mb_strpos($pass1,':')!== false)) $errorText = $language['password_bad'];
	
	if(strlen($errorText) > 0) //we've got error
	{
		return $errorText; //not going to write user data
	}

	$aUsers = getUsers(); //id:email:password:status
	
	for($i = 0; $i < count($aUsers); $i++) 		// Check user existance
	{
		$tmp = explode(':', $aUsers[$i]);
		if (mb_strtoupper($tmp[1]) == mb_strtoupper($user)) 
		{
			return $language['email_taken'];
			break;
		}
	}//for
	
    
	// Secure password string
	$userpass = md5($pass1);
	$strConfirmKey = md5(microtime().rand());
  	$strStatus = 'unconfirmed:'.$strConfirmKey;
	$strUserId = count($aUsers);
	if($strUserId == 0) //no users - first would be admin
	{
		$bUserJustConfirmed = true;
		$strStatus = 'admin';//overwrite
		$title = mb_strrichr($user,'@',true) . ' iSC SN';
		$errorText = 'Welcome, Admin ! =)'.'<br><a href="settings.php">'.$language['continue_to']. $language['site_settings_caption'] .'</a>';
//		lockDataDir(); //let's do it at least once, when admin is registering
		setConfig('site_title',$title);
		setConfig('site_description', $_SERVER['HTTP_HOST'] . ' iSC SN');	
		setConfig('members_only','no');				
 		$_SESSION['userName'] = $user;
 		$_SESSION['userId'] = '0';
		$_SESSION['validUser'] = true;   		
	}	
	else //not admin - must confirm email
	{
		sendConfirmationEmail(getSiteTitle(),$user,$pass1,"http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?confirm='.$strConfirmKey);
	}

	array_push($aUsers,"$strUserId:$user:$userpass:$strStatus");
	putUsers($aUsers); 
	return $errorText;
}

function sendConfirmationEmail($title,$user,$pass,$link)
{
	global $language;
	$strText = sprintf($language['confirmation_text'],$title,$user,$pass,$link);
	mail($user,$language['confirmation_subject'],$strText,
		"MIME-Version: 1.0\r\n".
		"Content-Type: text/plain; charset=utf-8 \r\n"	);
}



function getSiteTitle()
{
	return(getConfig('site_title'));
}

function getSiteDescription()
{
	return(getConfig('site_description'));
}

function loginUser($user,$pass)
{
	global $language;
	$errorText = $language['password_invalid'];
	$validUser = false;
	
	$aUsers = getUsers(); //id:email:password:status
	for($i = 0; $i < count($aUsers); $i++)
	{
		$tmp = explode(':', $aUsers[$i]);
		if (mb_strtoupper($tmp[1]) == mb_strtoupper($user)) //email ok
		{
      // User exists, check password
			if( (trim($tmp[2]) == trim(md5($pass))) ) //password ok
			{
				if(trim($tmp[3]) === 'banned')
				{
					$validUser = false;
					$errorText = $tmp[1]. ' ' . $language['is_banned'] . '<br>'. $tmp[4]; //ban reason	
				}
				else
				{
					if(trim($tmp[3]) === 'unconfirmed')
					{
						$validUser = false;
						$errorText = $tmp[1]. ' ' . $language['is_unconfirmed']; 
					}
					else //ok
					{ 
						$validUser= true;
						$_SESSION['userName'] = $user;
						$_SESSION['userId'] = $tmp[0];
						$errorText = '';
					}
				}
			}//password ok
      break;
  	}//email ok
	}//for loop
	
  if ($validUser == true) 
	{
		$_SESSION['validUser'] = true;
	}
  else
	{
		 $_SESSION['validUser'] = false;
	}
	return $errorText;	
}

function logoutUser(){
	global $language;
	unset($_SESSION['validUser']);
	unset($_SESSION['userName']);
	unset($_SESSION['userId']);	
}

function userIsLoggedIn()
{
 return ((isset($_SESSION['validUser'])) && ($_SESSION['validUser'] == true));
}

function userIsAdmin()
{
	return ($_SESSION['userId'] === '0'); //IMPORTANT! just '==0' will return true on empty SESSION !
}

//redirects to login.php if needed. handles setup/configuration redirects
function checkUser()
{
	global $language;
	$aUsers = getUsers();
// let's find out the prefix of the redirect URL
	$strSelf = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
	$strUrlPrefix = mb_strrichr($strSelf,'/',true);
	if (defined('IN_ISC')) //we are called from the /isc/index.php
	{
		$strUrlPrefix = mb_strrichr($strUrlPrefix,'/',true); //one up
	}

	if( (count($aUsers) == 0) ) //the website is not initialized yet
	{
		logoutUser(); //just in case
		$strRegisterPage = $strUrlPrefix.'/register.php';
		header("Location: $strRegisterPage ");//send the first dude to the admin registration
		exit;//we done
	}

//ok, if we here - admin record is present. let's see if the website is configured
	$strSiteTitle = getSiteTitle();	
	if($strSiteTitle == '') //empty title - not good
	{
		if(userIsAdmin())
		{
			$strSettingsPage = $strUrlPrefix.'/settings.php';
			header("Location: $strSettingsPage ");//send the first dude to the admin registration
			exit;//we done
		}
		else //not admin - some other dude
		{
			logoutUser(); //just in case
			$strLoginPage = $strUrlPrefix.'/login.php';
			header("Location: $strLoginPage ");//send the first dude to the admin login
			exit;//we done
		}
	}

//if we here, the webiste is initialized completely
	if( (getConfig('members_only') == 'yes') && (!userIsLoggedIn()) ) //initialized, and only logged vistors can see isc
	{
		$strLoginPage = $strUrlPrefix.'/login.php';
		header("Location: $strLoginPage");
		exit;
	}
	
}


function getRootPath() //used to get correct path to CSSs also !
{
	$strSelf = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
	return(mb_strrichr(mb_strrichr($strSelf,'/',true),'/',true));//two up
}

//not in use any more, we use htaccess now
function lockDataDir()
{
//	chmod(dirname(__FILE__) . '/data',0750);
}

$aGlobalUsers = array(); //so we don't read file every time
function getUsers()
{
	global $aGlobalUsers;
	if(count($aGlobalUsers) == 0)
	{ 
		$strUsersFileName = dirname(__FILE__) . "/data/users.txt";
		$bValueSet = false;
	 	$pfile = fopen($strUsersFileName,"c+");
 		if($pfile)
	 	{
		  rewind($pfile); 	
			if($pfile)
			{
			  while (!feof($pfile)) 
				{
  				$line = fgets($pfile);
			  	$tmp = explode(':', $line); //id:email:password:status
  				if(count($tmp) > 3 )
  				{
  					array_push($aGlobalUsers,trim($line));
			  	}
  			}//while
	  		fclose($pfile);
			}  	
 	 }
 	 else
 	 {
			die("Can't open $strUsersFileName (file:".__FILE__.', line:'.__LINE__.')');    
  	}
 	}
  return $aGlobalUsers;
}//EOF getUsers

function putUsers($aUsers)
{
	global $aGlobalUsers;
	unset($aGlobalUsers);
	$aGlobalUsers = $aUsers;
	$strUsersFileName = dirname(__FILE__) . "/data/users.txt";
 	$pfile = fopen($strUsersFileName,"w");
 	if($pfile)
 	{
	 	flock($pfile, LOCK_EX);
	  rewind($pfile);
  	for($i = 0; $i < count($aUsers); $i++)
	  {
  		fwrite($pfile, $aUsers[$i]."\n");
	  }
	  flock($pfile, LOCK_UN);
  	fclose($pfile);
  }
  else
  {
		die("Can't open $strUsersFileName (file:".__FILE__.', line:'.__LINE__.')');    

  }
}//EOF putUsers

function confirmUser($strCode)
{
	global $language;
	global $bUserJustConfirmed;
	$strContinue = '<br><a href="isc">'.$language['continue_to']. getSiteTitle().'</a>';
	$bUserFound = false;
	$aUsers = getUsers(); //id:email:password:status
  for($i = 0; $i < count($aUsers); $i++)
  {
  	$aOne = explode(':', $aUsers[$i]);
  	if(isset($aOne[4]) && ($aOne[4] == $strCode))
  	{
  		$bUserFound = true;
  		$aOne[3] = 'ok';
  		unset($aOne[4]);  		
  		$aUsers[$i] = implode(':', $aOne);
   		$_SESSION['userName'] = $aOne[1];
   		$_SESSION['userId'] = $aOne[0];
			$_SESSION['validUser'] = true;   		
  		putUsers($aUsers);
		$bUserJustConfirmed = true;
  		return $aOne[1] .$language['confirmed_ok'].$strContinue;
  	}
  }
	if($bUserFound === false)
	{
		return $language['unconfirmed_nokey'] . $strCode.$strContinue;	
	}
}//EOF confirmUser();

//@TODO - make $aGlobalConfig, so we don't read file every time
function getConfig($strValueName)
{
	$aConfig = array();
	$strConfigFileName = dirname(__FILE__) . '/data/config.txt';
	$aConfig = array();
	$bValueSet = false;
 	$pfile = fopen($strConfigFileName,"r+");
	if($pfile)
	{
	  while (!feof($pfile)) 
		{
  		$line = fgets($pfile);
	  	$tmp = explode('=', $line); //key=value
  		if(count($tmp) === 2 )
  		{
  			if($tmp[0] == $strValueName)
  			{
  				return trim($tmp[1]);
  			}
	  	}
  	}//while
  }
  fclose($pfile);
	return '';
}//EOF getConfig;

function setConfig($strValueName, $strValue)
{
	$strConfigFileName = dirname(__FILE__) . "/data/config.txt";
	$aConfig = array();
	$bValueSet = false;
 	$pfile = fopen($strConfigFileName,"c+");
	if($pfile)
	{
		flock($pfile, LOCK_EX);
	    while (!feof($pfile)) 
		{
  			$line = trim(fgets($pfile));
	  		$tmp = explode('=', $line); //key=value
  			if(count($tmp) === 2 )
  			{
  				$aConfig[$tmp[0]] = $tmp[1];
	  		}
  		}//while
  	}//if
  	else //couldn't open the file
  	{
		trigger_error("FATAL ERROR: Can't open/create '$strConfigFileName'. Please check the permissions.",E_USER_ERROR); //couldn't open data file
	}

	$aConfig[$strValueName] = $strValue;
  	rewind($pfile);
  	$aConfigKeys = array_keys($aConfig);

  	for($i = 0; $i < count($aConfig); $i++)
  	{
  		$line = $aConfigKeys[$i]."=".$aConfig[$aConfigKeys[$i]]."\n";
  		fwrite($pfile, $line);
  	
  	}
  	flock($pfile, LOCK_UN);
  	fclose($pfile);
}//EOF setConfig();

//This function gets content of .html file and returns it to include into the page.
// If .html file does not exists, .txt file with the coppesponding name is copied into .html file first.
// We go through this procedure to make sure the custom ads are not overwritten during the upgrade procedure.
function GetAdContent($strHtmlFileName)
{
	if(file_exists($strHtmlFileName))
	{
		return(file_get_contents($strHtmlFileName));	
	}
	else //file does not exist
	{
		$strTxtFileName = str_replace('.html','.txt',$strHtmlFileName);

		if(file_exists($strTxtFileName))
		{
			copy($strTxtFileName,$strHtmlFileName);
			return(file_get_contents($strHtmlFileName));
		}
		else //even .txt not present
		{
			return('');
		}
	}//else - no .html file
}

//during the installation we need to create two data filed in 'data' dir, and  .htaccess in 'isc' dir. 
//so, we will try to make the directories writable, and if we fail - no need to continue
function checkDirsWritable()
{
	$data_dir = dirname(__FILE__) . '/data';
	$isc_dir = dirname(__FILE__) . '/isc';
//755 suppose to be safe, on some configurations 777 causes 500
//although let's not to play with the attributes - hard to detect error if we change attributes all the time
//	chmod($data_dir,0755);  
//	chmod($isc_dir,0755); //we will lock them later
	$data_perms = substr(sprintf('%o', fileperms($data_dir)), -4);
	$isc_perms = substr(sprintf('%o', fileperms($isc_dir)), -4);
	if( (!@is_writable($data_dir)) || (!@is_writable($isc_dir)) ) //@ - suppress warning
	{
		die("FATAL ERROR: The  <br/> <b>$data_dir</b> ($data_perms)<br/>and <br/> <b>$isc_dir</b> ($isc_perms)<br/>must be writable to install.");
	}
}// checkDirWritable()
?>
