<?php
require_once('common.php');

$error = '0';

$aUsers = getUsers();
if(count($aUsers) == 0) //no admin
{
	header('Location: register.php');
}

if (isset($_POST['submitBtn'])){
	// Get user input
	$username = isset($_POST['username']) ? $_POST['username'] : '';
	$password = isset($_POST['password']) ? $_POST['password'] : '';
        
	// Try to login the user
	$error = loginUser($username,$password);
	if ($error == '') {
		header('Location: isc/index.php');
	}
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>

   <!-- TITLE -->
      <title><?php echo(getSiteTitle()); ?></title>

   <!-- INFO(S) -->
      <META name=DESCRIPTION content="<?php echo(getSiteDescription()); ?>">

   <!-- STYLESHEET(S) -->
      <link href="style/style.css" rel="stylesheet" type="text/css" />

</head>
   <body>
<!-- START -->
    <div id="main">
<?php if ($error != '') {?>
      <div class="caption"><?php echo($language['login_caption']); ?></div>
      <div id="icon">&nbsp;</div>
      <form id="main_form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="loginform">
        <table width="100%">
          <tr><td align="right"><?php echo($language['email']); ?></td><td> <input class="text" name="username" type="text"  /></td></tr>
          <tr><td align="right"><?php echo($language['password']); ?></td><td> <input class="text" name="password" type="password" /></td></tr>
          <tr><td colspan="2" align="center"><input class="text" type="submit" name="submitBtn" value="<?php echo($language['login_action']); ?>" /></td></tr>
        </table>  
      </form>
      
      &nbsp;<a href="register.php"><?php echo($language['register_action']); ?></a>
      
<?php 
}   
    if (isset($_POST['submitBtn'])){

?>
      <div class="caption"><?php echo($language['login_result']); ?></div>
      <div id="icon2">&nbsp;</div>
      <div id="result">
        <table width="100%"><tr><td><br/>
<?php
 echo $error;

?>
		<br/><br/><br/></td></tr></table>
	</div>
<?php            
    }
?>
	<div id="source"></div>
    </div>

<!-- END -->
   </body>
</html>
