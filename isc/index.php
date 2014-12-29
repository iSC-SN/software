<?php
//here we have all system functionality
	define('IN_ISC', true); //so we can call gra4 scripts

	include_once('../common.php');
	include_once('functions.php'); //current dir. that's where the functionality on iSC SN is. Callback! (must be before checkUser())
	
	
	checkDirsWritable();
	
	checkUser(); //'IN_ISC' is used there !

	

//Uncomment to test functionality of local functions. Good for debugging iSC SN for new platforms.
//GRA4TestLocalFunctions();

//let's make sure we can run
	if(!isc_is_private_ip($_SERVER['REMOTE_ADDR']))
	{
		trigger_error('FATAL ERROR: iSC SN is not intended to operate on a local machine.',E_USER_ERROR); //accordingly to http://php.net/manual/en/errorfunc.constants.php Execution of the script is halted. Sorry.
	}

//to operate we need .httaccess file with correct RewriteBase	
	$bRewriteBaseIsSet = iSCHtaccessWriter(true); 
	if($bRewriteBaseIsSet !== true)
	{
		trigger_error('FATAL ERROR: ' . $bRewriteBaseIsSet,E_USER_ERROR); //couldn't create .httaccess - failed! =( accordingly to http://php.net/manual/en/errorfunc.constants.php Execution of the script is halted. Sorry.
	}


	$bFrameNeeded = true; //if true, we will create header and footer of the page
	$strRemoteContent = ISCFetchRemoteContent($bFrameNeeded); //in functions.php

	if($bFrameNeeded === false) //we don't need frame. it's CSS, picture, or something like that
	{
		exit($strRemoteContent); //we done
	}
?>

<!DOCTYPE html>
<html>
<head>

   <!-- TITLE -->
      <title><?php echo(getSiteTitle()); ?></title>

   <!-- INFO(S) -->
      <META name=DESCRIPTION content="<?php echo(getSiteDescription()); ?>">

   <!-- STYLESHEET(S) -->
      <link href="<?php echo(getRootPath()); ?>/style/style.css" rel="stylesheet" type="text/css" />

</head>
   <body>
<!-- START -->

<!-- header start -->
<table width='100%' border=0 >
	<tr>
		<td  width='100%'>
<div id="main" class="main_page_main" style="width:100%;">
<div class="caption"><?php echo(getSiteTitle()); ?></div>
<div id="icon">&nbsp;</div>
<div id="result">
<?php if(userIsLoggedIn())
{
?>
<a id="a_logout" href="<?php echo(getRootPath()); ?>/logout.php"><?php echo($language['logout_action'])?> [<?php echo $_SESSION['userName']; ?>] </a>
<?php
// in JavaScipt use the existence of 'id="a_logout"' to determine if user is logged in.  
}
else
{
?>
<a href="<?php echo(getRootPath()); ?>/login.php"><?php echo($language['login_caption']) ?> </a>
 / 
<a href="<?php echo(getRootPath()); ?>/register.php"><?php echo($language['register_caption']) ?> </a>
<?php
}
if(userIsAdmin())
{
?>
<br><a id="a_admin_settings" href="<?php echo(getRootPath()); ?>/settings.php"><?php echo($language['settings_action']) ?></a>
&nbsp;&nbsp;<a id="a_admin_ads" href="<?php echo(getRootPath()); ?>/ads.php"><?php echo($language['ads_action']) ?></a>

<?php
// in JavaScipt use the existence of 'id="a_admin_settings"' to determine if the admin is logged in 

}
?>

</div>		
		</td>
		<td align='right'>
<?php echo(GetAdContent("../ads/top.html")); ?> 
		</td>
	</tr>
</table>	
<!-- header end -->



 
<!-- body start -->
<table width=100% style="border:solid red 0px;"> 
	<tr>

<td valign='top' style='width:0px;'>
<?php echo(GetAdContent("../ads/left.html")); ?>
</td>
		
<td valign='top' style1='width:100%;min-width1:1000px;'>
<?php echo($strRemoteContent); ?>
</td>

<td valign='top'  style='width:0px;'>
<?php echo(GetAdContent("../ads/right.html")); ?>
</td>
	</tr>
</table>
<!-- body end -->

<!-- footer start -->
<hr>
<div style="display:block;border:solid red 0px;">
<?php echo(GetAdContent("../ads/bottom.html")); ?> 
</div>
<!-- footer end -->

<!-- END -->
   </body>
</html>