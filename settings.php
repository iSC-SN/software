<?php
require_once('common.php');
checkUser(); //if there is no admin we sending the current guy to register

if(!userIsAdmin())
{
	header('Location: isc/index.php');
	exit;//we done. Admins only!
}
$strResult = '';
$aUsers = getUsers(); //id:email:password:status
$aAdmin = explode(':',$aUsers[0]);
//print_r($_POST);
$strSubject = $_POST['subject'];
$strBody = $_POST['body'];

if(isset($_POST['SaveSiteSettings']))
{
	if(mb_strlen(trim($_POST['title'])) == 0) //title is not set
	{
		$strResult = $language['title_invalid'];
	}
	else //at least we have the title
	{
		if(isset($_POST['members_only']))
		{
			setConfig('members_only','yes');
		}
		else
		{
			setConfig('members_only','no');
		}
		setConfig('site_description',$_POST['description']);
		setConfig('site_title',$_POST['title']);	
		$strResult = $language['saved_ok'];
	}
}

if(isset($_POST['SaveAdminSettings']))
{
	$strOldPassword = $_POST['old_password'];
	$strNewPassword = $_POST['new_password'];
	$strNewEmail = $_POST['email'];
	if( (trim($aAdmin[2]) == trim(md5($strOldPassword))) ) //password ok
	{
		if(filter_var($strNewEmail, FILTER_VALIDATE_EMAIL) !== false ) //email is good
		{
			if(strlen(trim($strNewPassword)) > 0) //going to update the pasword
			{
				if (mb_strlen($strNewPassword) >= mb_strlen('****'))
				{
					if ((mb_strpos($strNewPassword,':')=== false)) 
					{
						$aAdmin[2] = md5($strNewPassword);
					}
					else
					{
						$strResult = $language['password_bad'];
					}
				}
				else
				{
					$strResult = $language['password_short'];
				}
			}
			else
			{
			 //keep old password
			}
			$_SESSION['userName'] = $strNewEmail;
			$aAdmin[1] = $strNewEmail;
			$aUsers[0] = implode(':',$aAdmin);
			putUsers($aUsers); 
			$strResult = $language['saved_ok'];
		}
		else
		{
			$strResult = $language['email_bad'];
		}
	}
	else
	{
		$strResult = $language['old_password_bad'];
	}
//print_r($_POST); print_r($aAdmin);die;
}

$strTitle = getSiteTitle();
$strDescription = getSiteDescription();
$strMembersOnly = getConfig('members_only');

$strMembersOnlyChecked = '';
if($strMembersOnly == 'yes')
{
	$strMembersOnlyChecked = 'CHECKED';
}

$strUserTableTRs = "\n<tr><td nowrap><button title='Invert Selection' onClick='InvertUserSelection();return false;'>&bull;</button></td><td nowrap>".$language['users_settings_caption']."</td><td nowrap></td></tr>\n";
$iEmailSent = 0;
for($i = 0; $i < count($aUsers); $i++) 		
{
	$tmp = explode(':', $aUsers[$i]);//id:email:password:status
	if(isset($_POST['ucd_'.$tmp[0]]))
	{
		mail($tmp[1],$strSubject,$strBody,
			"MIME-Version: 1.0\r\n".
			"Content-Type: text/plain; charset=utf-8 \r\n"	);
		$iEmailSent++;
	}	
	if(isset($_POST['ban_user_'.$tmp[0]]))
	{
		$tmp[3] = 'banned';
		$strResult = $language['ban_caption'] . " : " .$tmp[1];
	}	
	if(isset($_POST['unban_user_'.$tmp[0]]))
	{
		$tmp[3] = 'ok';
		$strResult = $language['unban_caption'] . " : " .$tmp[1];
	}	
	if(isset($_POST['confirm_user_'.$tmp[0]]))
	{
		$tmp[3] = 'ok';
		unset($tmp[4]);
		$strResult = $language['confirm_caption'] . " : " .$tmp[1];
	}
	$aUsers[$i] = implode(':',$tmp);	
		
	$id = $tmp[0];	
	$email = $tmp[1];
	$status = $tmp[3];
	$banreason = '';
	if(isset($tmp[4]))
	{
		$banreason = $tmp[4]; 
	}
	
	$button = '';
	if($status == 'admin' || $id == '0')
	{
		$button = 'Admin';
	}
	if($status == 'unconfirmed')
	{
		$button = "<input class='text' type='submit' name='confirm_user_$id' value='".$language['confirm_caption']."'/>";
	}
	if($status == 'ok')
	{
		$button = "<input class='text' type='submit' name='ban_user_$id' value='".$language['ban_caption']."'/>";
	}	
	if($status == 'banned')
	{
		$button = "<input class='text' type='submit' name='unban_user_$id' value='".$language['unban_caption']."'/>";			
	}		
	$check= "<input id='utd_$id' class='text' name='ucd_$id' type='checkbox' />";
	$strUserTableTRs .= "<tr title='$email , id: $id, status: $status'><td nowrap>$check</td><td nowrap>$email</td><td nowrap id='utd_$id'>$button</td></tr>\n";
}//user loop
if($iEmailSent > 0)
{
	$strResult = sprintf($language['email_sent'],$iEmailSent);
}
putUsers($aUsers);	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
   <title><?php echo(getSiteTitle()); ?></title>
   <META name=DESCRIPTION content="<?php echo(getSiteDescription()); ?>">   
   
   <link href="style/style.css" rel="stylesheet" type="text/css" />
</head>
<script language="javascript">
var iMaxUserId = <?php echo($id)?>;
function InvertUserSelection()
{
	for(var i=0; i <= iMaxUserId; i++)
	{
		oUserCheck = document.getElementById('utd_'+i);
		if(oUserCheck)
		{
			oUserCheck.checked = !oUserCheck.checked;
		}
	}
}
</script>
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
	
      <div class="caption"><?php echo($language['site_settings_caption']); ?></div>
      <div id="icon2">&nbsp;</div>
      <form id="site_form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="siteform">
        <table width="100%">
          <tr><td align="right"><?php echo($language['title_caption']); ?></td><td> <input class="text" name="title" type="text" value="<?php echo($strTitle);?>" /></td></tr>
          <tr><td align="right"><?php echo($language['description_caption']); ?></td><td> <input class="text" name="description" type="text" value="<?php echo($strDescription);?>" /></td></tr>
          <tr><td colspan="2" align="center"><input class="text" name="members_only" type="checkbox" <?php echo($strMembersOnlyChecked);?> /><?php echo($language['members_only_caption']); ?> </td></tr>
          <tr><td colspan="2" align="center"><input class="text" type="submit" name="SaveSiteSettings" value="<?php echo($language['save_site_action']); ?>" /></td></tr>
        </table>  
      </form>

	  
	  
      <div class="caption"><?php echo($language['admin_settings_caption']); ?></div>
      <div id="icon2">&nbsp;</div>
      <form id="admin_form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="adminform">
        <table width="100%">
	      <tr><td align="right"><?php echo($language['password_old']); ?></td><td> <input class="text" name="old_password" type="password" value="" /></td></tr>
		  <tr><td align="right"><?php echo($language['email']); ?></td><td> <input class="text" name="email" type="text" value="<?php echo($aAdmin[1]);?>" /></td></tr>
          <tr><td align="right"><?php echo($language['password_new']); ?></td><td> <input class="text" name="new_password" type="text" value="" /></td></tr> 
          <tr><td colspan="2" align="center"><input class="text" type="submit" name="SaveAdminSettings" value="<?php echo($language['save_admin_action']); ?>" /></td></tr>
        </table>  
      </form>
	  
      <div class="caption"><?php echo($language['users_settings_caption']); ?></div>
      <div id="icon">&nbsp;</div>
      <form id="user_form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="userform">
        <table width="100%" style="border: dashed lightgray 1px;"><?php echo($strUserTableTRs); ?></table>  
        <table width="100%">
          <tr><td align="right"><?php echo($language['subject_caption']); ?></td><td> <input class="text" name="subject" type="text" value="<?php echo($strSubject); ?>" /></td></tr>
          <tr><td align="right"><?php echo($language['text_caption']); ?></td><td> <textarea class="text" name="body" ><?php echo($strBody); ?></textarea></td></tr>
          <tr><td colspan="2" align="center"><input class="text" type="submit" name="EmailToSelected" value="<?php echo($language['email_selected_caption']); ?>" /></td></tr>
        </table>  
      </form>

      &nbsp;<a href="isc" id="link_back"><?php echo($language['back_to'] . $strTitle); ?></a>
      
      
</div>
</body>   
