<?php
require_once('common.php');
checkUser(); //if there is no admin we sending the current guy to register

if(!userIsAdmin())
{
	header('Location: isc/index.php');
	exit;//we done. Admins only!
}

$strTitle = getSiteTitle();

$strResult = '';

if(isset($_POST['SaveAdsSettings']))
{

	if (get_magic_quotes_gpc()) 
	{
		function stripslashes_gpc(&$value)
		{
			$value = stripslashes($value);
		}
		array_walk_recursive($_POST, 'stripslashes_gpc');
	}
//print_r($_POST); die;

	if(
		file_put_contents("ads/top.html",$_POST['ad_top'])
		&&
		file_put_contents("ads/left.html",$_POST['ad_left'])
		&&
		file_put_contents("ads/right.html",$_POST['ad_right'])
		&&
		file_put_contents("ads/bottom.html",$_POST['ad_bottom'])	
	  )
	 {
		$strResult = $language['saved_ok'];
	 }
	 else
	 {
		$strResult = $language['saved_error'];
	 }
}

$strAdTop = GetAdContent("ads/top.html");
$strAdLeft = GetAdContent("ads/left.html");
$strAdRight = GetAdContent("ads/right.html");
$strAdBottom = GetAdContent("ads/bottom.html");


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
   <title><?php echo(getSiteTitle()); ?></title>
   <META name=DESCRIPTION content="<?php echo(getSiteDescription()); ?>">   
   
   <link href="style/style.css" rel="stylesheet" type="text/css" />
</head>

<body>
    <div id="main">
	
<?php
if($strResult !== '')
{
	echo("
      <div id='result'>
        <table width='100%'><tr><td>
$strResult
	<br/></td></tr></table>
	</div>
			");
}
?>	
	
      <div class="caption"><?php echo($language['ads_settings_caption']); ?></div>
      <div id="icon2">&nbsp;</div>
      <form id="site_form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="siteform">
        <table width="100%">
			<tr><td align="left"><?php echo($language['ad_title_top']); ?><br/> <textarea rows="5" cols="30" class="ad_textarea"  name="ad_top" type="text"><?php echo($strAdTop);?></textarea><hr/></td></tr>
			<tr><td align="left"><?php echo($language['ad_title_left']); ?><br/> <textarea rows="5" cols="30" class="ad_textarea"  name="ad_left" type="text"><?php echo($strAdLeft);?></textarea><hr/></td></tr>
			<tr><td align="left"><?php echo($language['ad_title_right']); ?><br/> <textarea rows="5" cols="30" class="ad_textarea"  name="ad_right" type="text"><?php echo($strAdRight);?></textarea><hr/></td></tr>
 			<tr><td align="left"><?php echo($language['ad_title_bottom']); ?><br/> <textarea rows="5" cols="30" class="ad_textarea"  name="ad_bottom" type="text"><?php echo($strAdBottom);?></textarea><hr/></td></tr>

          <tr><td align="center"><input class="text" type="submit" name="SaveAdsSettings" value="<?php echo($language['save_ad_action']); ?>" /></td></tr>
        </table>  
      </form>

	 


      &nbsp;<a href="isc" id="link_back"><?php echo($language['back_to'] . $strTitle); ?></a>
      
      
</div>
</body>
