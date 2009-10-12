<?php
/*
Plugin Name: Change Password / E-mail
Plugin URI: http://www.scottkclark.com/
Description: Creates Change Password and Change E-mail pages under Users to circumvent default user profile editor. Best used in conjunction with Adminimize.
Version: 0.1
Author: Scott Kingsley Clark
Author URI: http://www.scottkclark.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
function change_password_email_init()
{
	add_submenu_page( 'users.php', __('Change Password', 'change-password-email'), __('Change Password', 'change-password-email'), 0, 'change-password', 'change_password_email_pw' );
	add_submenu_page( 'users.php', __('Change E-mail', 'change-password-email'), __('Change E-mail', 'change-password-email'), 0, 'change-email', 'change_password_email_email' );
}
function change_password_email_check()
{
	if(isset($_GET['page'])&&($_GET['page']=='change-password'||$_GET['page']=='change-email'))
	{
		wp_enqueue_script('user-profile');
		wp_enqueue_script('password-strength-meter');
	}
}
function change_password_email_pw()
{
	$title = __('Change Password');
	$what = 'change-password';
	
	wp_reset_vars(array('action', 'redirect', 'profile', 'user_id', 'wp_http_referer'));
	
	$wp_http_referer = remove_query_arg(array('update', 'delete_count'), stripslashes($wp_http_referer));
	
	$user_id = (int) $user_id;
	
	if ( !$user_id ) {
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
	} elseif ( !get_userdata($user_id) ) {
		wp_die( __('Invalid user ID.') );
	}
	switch ($action) {
	case 'update':
	
	check_admin_referer('update-user_' . $user_id);
	
	if ( !current_user_can('edit_user', $user_id) )
		wp_die(__('You do not have permission to edit this user.'));
	
	do_action('personal_options_update', $user_id);
	global $wpdb;
	if(isset($_POST['pass1'])&&isset($_POST['pass2'])&&$_POST['pass1']==$_POST['pass2'])
		$errors = $wpdb->query("UPDATE $wpdb->users SET user_pass = '".wp_hash_password($_POST['pass1'])."' WHERE ID = $user_id");
	
	if ( $errors ) {
		$redirect = "profile.php?page=".$what."&updated=true";
		wp_redirect($redirect);
		exit;
	}
	
	default:
	$profileuser = get_user_to_edit($user_id);
	
	if ( !current_user_can('edit_user', $user_id) )
		wp_die(__('You do not have permission to edit this user.'));
	?>
	
	<?php if ( isset($_GET['updated']) ) : ?>
	<div id="message" class="updated fade">
		<p><strong><?php _e('User updated.') ?></strong></p>
	</div>
	<?php endif; ?>
	<?php if ( isset( $errors ) && is_wp_error( $errors ) ) : ?>
	<div class="error">
		<ul>
		<?php
		foreach( $errors->get_error_messages() as $message )
			echo "<li>$message</li>";
		?>
		</ul>
	</div>
	<?php endif; ?>
	
	<div class="wrap" id="profile-page">
	<?php screen_icon(); ?>
	<h2><?php echo esc_html( $title ); ?></h2>
	
	<form id="your-profile" action="" method="post">
	<?php wp_nonce_field('update-user_' . $user_id) ?>
	<?php if ( $wp_http_referer ) : ?>
		<input type="hidden" name="wp_http_referer" value="<?php echo esc_url($wp_http_referer); ?>" />
	<?php endif; ?>
	<p>
	<input type="hidden" name="from" value="profile" />
	<input type="hidden" name="checkuser_id" value="<?php echo $user_ID ?>" />
	</p>
	
	<table class="form-table">
	<?php
	$show_password_fields = apply_filters('show_password_fields', true, $profileuser);
	if ( $show_password_fields ) :
	?>
	<tr id="password">
		<th><label for="pass1"><?php _e('New Password'); ?></label></th>
		<td><input type="password" name="pass1" id="pass1" size="16" value="" autocomplete="off" /> <span class="description"><?php _e("If you would like to change the password type a new one. Otherwise leave this blank."); ?></span><br />
			<input type="password" name="pass2" id="pass2" size="16" value="" autocomplete="off" /> <span class="description"><?php _e("Type your new password again."); ?></span><br />
			<div id="pass-strength-result"><?php _e('Strength indicator'); ?></div>
			<p class="description indicator-hint"><?php _e('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).'); ?></p>
		</td>
	</tr>
	<?php endif; ?>
	</table>
	<p class="submit">
		<input type="hidden" name="user_login" id="user_login" value="<?php echo esc_attr($profileuser->user_login); ?>" />
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr($user_id); ?>" />
		<input type="submit" class="button-primary" value="<?php esc_attr_e('Update Profile') ?>" name="submit" />
	</p>
	</form>
	</div>
	<?php
	break;
	}
}
function change_password_email_email()
{
	$title = __('Change E-mail');
	$what = 'change-email';
	
	wp_reset_vars(array('action', 'redirect', 'profile', 'user_id', 'wp_http_referer'));
	
	$wp_http_referer = remove_query_arg(array('update', 'delete_count'), stripslashes($wp_http_referer));
	
	$user_id = (int) $user_id;
	
	if ( !$user_id ) {
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
	} elseif ( !get_userdata($user_id) ) {
		wp_die( __('Invalid user ID.') );
	}
	$updated = false;
	if(isset($_POST['action'])&&$_POST['action']=='update')
	{
	check_admin_referer('update-user_' . $user_id);
	
	if ( !current_user_can('edit_user', $user_id) )
		wp_die(__('You do not have permission to edit this user.'));
	
	do_action('personal_options_update', $user_id);
	global $wpdb;
	if(isset($_POST['email']))
	{
		$wpdb->query($wpdb->prepare("UPDATE $wpdb->users SET user_email = '%s' WHERE ID = $user_id",$_POST['email']));
		$updated = true;
	}
	}
	$profileuser = get_user_to_edit($user_id);
	
	if ( !current_user_can('edit_user', $user_id) )
		wp_die(__('You do not have permission to edit this user.'));
	?>
	
	<?php if ( $updated ) : ?>
	<div id="message" class="updated fade">
		<p><strong><?php _e('User updated.') ?></strong></p>
	</div>
	<?php endif; ?>
	<?php if ( isset( $errors ) && is_wp_error( $errors ) ) : ?>
	<div class="error">
		<ul>
		<?php
		foreach( $errors->get_error_messages() as $message )
			echo "<li>$message</li>";
		?>
		</ul>
	</div>
	<?php endif; ?>
	
	<div class="wrap" id="profile-page">
	<?php screen_icon(); ?>
	<h2><?php echo esc_html( $title ); ?></h2>
	
	<form id="your-profile" action="" method="post">
	<?php wp_nonce_field('update-user_' . $user_id) ?>
	<?php if ( $wp_http_referer ) : ?>
		<input type="hidden" name="wp_http_referer" value="<?php echo esc_url($wp_http_referer); ?>" />
	<?php endif; ?>
	<p>
	<input type="hidden" name="from" value="profile" />
	<input type="hidden" name="checkuser_id" value="<?php echo $user_ID ?>" />
	</p>
	
	<table class="form-table">
	<tr>
		<th><label for="email"><?php _e('E-mail'); ?></label></th>
		<td><input type="text" name="email" id="email" value="<?php echo esc_attr($profileuser->user_email) ?>" class="regular-text" /></td>
	</tr>
	</table>
	<p class="submit">
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr($user_id); ?>" />
		<input type="submit" class="button-primary" value="<?php esc_attr_e('Update Profile') ?>" name="submit" />
	</p>
	</form>
	</div>
	<?php
}
add_action('admin_menu','change_password_email_init');
add_action('admin_init','change_password_email_check');