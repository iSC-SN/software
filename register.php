<?php
	require_once('common.php');

	if(isset($_GET['confirm']))
	{
		$error = confirmUser($_GET['confirm']);
	}
	else
	{
		if (isset($_POST['submitBtn']))
		{
		// Get user input
			$username  = isset($_POST['username']) ? $_POST['username'] : '';
			if(empty($_POST['password_is_clear']))
			{
				$password1 = isset($_POST['password1']) ? $_POST['password1'] : '';
				$password2 = isset($_POST['password2']) ? $_POST['password2'] : '';
			}
			else
			{
				$password1 = isset($_POST['password_clear']) ? $_POST['password_clear'] : '';
				$password2 = isset($_POST['password_clear']) ? $_POST['password_clear'] : '';
			}
 		// Try to register the user
			$error = registerUser($username,$password1,$password2);
		}
//		else
		{
			$aUsers = getUsers();
			if(count($aUsers) == 0) //it's admin registering
			{
				$language['email'] = $language['admin_email'];
				$language['register_caption'] = $language['admin_register_caption'];
			}
		}
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
      <link href="style/style.css" rel="stylesheet" type="text/css" />

   <!-- SCRIPT(S) -->
      <script>
      function PassTypeChanged()
      {
      	      var oCB = document.getElementById("cb_pt");
      	      var oP1 = document.getElementById("ip_1");
	      var oP2 = document.getElementById("ip_2");
	      var oPC = document.getElementById("ip_c");
	      var oTRP1 = document.getElementById("trp_1");
	      var oTRP2 = document.getElementById("trp_2");
	      var oTRPC = document.getElementById("trp_c");

	      if(oCB.checked)
	      {
      //alert("checked");
		      oTRP1.style.display = "none";
		      oTRP2.style.display = "none";
		      oTRPC.style.display = "";
		      oPC.focus();
		      oP1.value='';
		      oP2.value='';
	      }
	      else
	      {
      //alert("UPchecked");	
		      oTRP1.style.display = "";
		      oTRP2.style.display = "";
		      oTRPC.style.display = "none";
		      oP1.focus();
	      }
      }
      </script>


</head>
   <body>
<!-- START -->

			&nbsp;<a style="float:right;text-decoration:none;" href="http://sn.isc"><small>powered by iSC SN</small></a>&nbsp;

    <div id="main">
<?php if ( ((!isset($_POST['submitBtn'])) || ($error != '')) && ($bUserJustConfirmed === false)  ) {?>
      <div class="caption"><?php echo($language['register_caption']); ?></div>
      <div id="icon">&nbsp;</div>
      <form id="main_form"  action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="registerform">
        <table width="100%">
          <tr><td align="right"><?php echo($language['email']); ?></td><td> <input class="text" name="username" type="text"  /></td></tr>
          <tr id="trp_1" style="display:none;"><td align="right"><?php echo($language['password']); ?></td><td> <input class="text" name="password1" type="password" id="ip_1"/></td></tr>
          <tr id="trp_2" style="display:none;"><td align="right"><?php echo($language['password2']); ?></td><td> <input class="text" name="password2" type="password" id="ip_2" /></td></tr>
		  <tr id="trp_c"><td align="right"><?php echo($language['password']); ?></td><td> <input class="text" name="password_clear" type="text" id="ip_c" /></td></tr>
		  <tr><td colspan="2" align="center"> <input  name="password_is_clear" type="checkbox" checked onchange="PassTypeChanged();" id="cb_pt"/> <?php echo($language['password_clear']); ?></td></tr>
          <tr><td colspan="2" align="center"><input class="text" type="submit" name="submitBtn" value="Register" /></td></tr>
        </table>  
      </form>
			&nbsp;<a href="login.php"><?php echo($language['login_action']); ?></a>&nbsp;   
				
     
<?php 
}   
    if ( (isset($_POST['submitBtn'])) || (isset($_GET['confirm'])) ){

?>
      <div class="caption"><?php echo($language['register_result']); ?></div>
      <div id="icon2">&nbsp;</div>
      <div id="result">
        <table width="100%"><tr><td><br/>
<?php
	if ($error == '') {
		echo $language['register_ok'] .$username;
		echo '<br><a href="isc" id="link_back">'.$language['continue_to']. getSiteTitle().'</a>';
	}
	else echo $error;

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
