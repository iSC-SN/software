<?php
/*
	Do not modify this file. 
	If you would like to change messages, or translate iSC SN to your language, use file '/lang_extra.php'
	In the file '/lang_extra.php' modifications should be made like: 
		$language['login_caption'] = 'LOGIN'; //would be use instead of 'Login'
*/

	$language = Array(
	'login_caption' => 'Login',
	'register_caption' => 'Register',
	'admin_register_caption' => 'Admin Register',
	'admin' => 'Admin ',	
	'admin_email' => 'Admin E-Mail',		
	'email' => 'E-Mail:',	
	'password' => 'Password:',	
	'password2' => 'Confirm password:',	
	'password_clear' => 'Clear Password',	
	'password_old' => 'Current Password:',
	'password_new' => 'New Password:',	
	'old_password_bad' => 'Password is incorrect',
	'login_action' => 'Login',	
	'register_action' => 'Register new User',
	'logout_action' => 'Logout',
	'login_result' => 'Login result:',
	'register_result' => 'Registration result:',
	'passwords_not_mach' => 'Passwords are not identical!',		
	'password_short' => 'Password is too short!',	
	'password_bad' => 'Password contains forbidden symbols!',	
	'email_bad' => 'EMail address is malformed!',	
	'email_taken' => 'The selected EMail is already registered!',	
	'password_invalid' => 'Invalid username or password!',	
	'register_ok' => 'Successfull registration!<br>Please check your email to confirm ',
	'confirmation_subject' => 'Please confirm your membership',		
	'confirmation_text' => "Welcome to %s!\r\nYour login information:\r\nEmail %s\r\nPassword %s\r\nTo confirm your email please visit\r\n%s\r\n",
	'continue_to' => 'Continue to ',	
	'back_to' => 'Back to ',		
	'is_banned' => 'is banned. ', 
	'is_unconfirmed' => 'is not confirmed yet. Please check your email for the confirmation link.',
	'unconfirmed_nokey' => 'There is no user awaiting confirmation with the key ',
	'confirmed_ok' => ' is confirmed. Thank you !',
	'settings_action' => 'Settings (admin)',
	'site_settings_caption' => 'Site Settings',	
	'admin_settings_caption' => 'Admin Settings',	
	'title_caption' => 'Site Title:',	
	'title_invalid' => 'Site Title may not be empty!',	
	'description_caption' => 'Site Description:',	
	'members_only_caption' => 'Members Only',	
	'save_site_action' => 'Save Site Settings',	
	'save_admin_action' => 'Save Admin Settings',	
	'users_settings_caption' => 'Users',
	'ban_caption'	 => 'Ban',
	'confirm_caption'	 => 'Confirm',	
	'ban_reason_caption'	 => 'Ban reason:',		
	'unban_caption'	 => 'Unban',
	'email_selected_caption'	 => 'Send EMail to selected users',
	'subject_caption'	 => 'Subject:',
	'text_caption'	 => 'Text:',
	'saved_ok'	 => 'Settings Saved',
	'saved_error'	 => 'Unexpected Error!',
	'email_sent'	 => 'Sent %d emails',	
	'ads_settings_caption' => 'Ads Settings',	
	'ads_action' => 'Ads (admin)',
	'ad_title_left' => 'Left',
	'ad_title_right' => 'Right',
	'ad_title_top' => 'Top',
	'ad_title_bottom' => 'Bottom',
	'save_ad_action' => 'Save Ads Settings',	
	);
//include language modifications. 
//file lang_extra.php is not in the distribution, so it's not going to be overwritten during the upgrade
if(file_exists(dirname(__FILE__).'/lang_extra.php')) //redundant? include not suppose to error out 
{
	include(dirname(__FILE__).'/lang_extra.php');
}
