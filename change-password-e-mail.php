<?php
/*
Plugin Name: Change Password and E-mail
Plugin URI: http://scottkclark.com/wordpress/change-password-e-mail/
Description: Creates simplified Change Password and Change E-mail pages under Users menu to replace "Your Profile"
Version: 1.0
Author: Scott Kingsley Clark
Author URI: http://scottkclark.com/
Text Domain: change-password-email

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

load_plugin_textdomain('change-password-email', false, basename(dirname(__FILE__)) . 'languages/');

add_action('admin_menu', 'change_password_email_init');
add_action('admin_init', 'change_password_email_check');

function change_password_email_init () {
    add_submenu_page('users.php', __('Change Password', 'change-password-email'), __('Change Password', 'change-password-email'), 0, 'change-password', 'change_password_email_pw');
    add_submenu_page('users.php', __('Change E-mail', 'change-password-email'), __('Change E-mail', 'change-password-email'), 0, 'change-email', 'change_password_email_email');
}

function change_password_email_check () {
    if (isset($_GET['page']) && ($_GET['page'] == 'change-password' || $_GET['page'] == 'change-email')) {
        wp_enqueue_script('user-profile');
        wp_enqueue_script('password-strength-meter');
        if (!empty($_POST)) {
            global $wpdb, $user_ID, $user_email;
            get_currentuserinfo();
            $user = get_userdata($user_ID);
            if (isset($_POST['pass1']) && isset($_POST['pass2']) && !empty($_POST['pass1']) && $_POST['pass1'] == $_POST['pass2']) {
                $update = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->users} SET `user_pass` = %s WHERE `ID` = %d", array(wp_hash_password($_POST['pass1']), $user_ID)));
                if (!is_wp_error($update)) {
                    wp_cache_delete($user_ID, 'users');
                    wp_cache_delete($user->user_login, 'userlogins');
                    wp_logout();
                    wp_signon(array('user_login' => $user->user_login,
                                   'user_password' => $_POST['pass1']));
                    ob_start();
?>
                <div id="message" class="updated fade">
                    <p><strong><?php _e('Password updated.') ?></strong></p>
                </div>
<?php
                                    $_POST['post_msg'] = ob_get_clean();
                }
                if (is_wp_error($update)) {
                    ob_start();
?>
                <div class="error">
                    <ul>
<?php
                    foreach ($update->get_error_messages() as $message) {
?>
                        <li><?php echo $message; ?></li>
<?php
                    }
?>
                    </ul>
                </div>
<?php
                    $_POST['post_msg'] = ob_get_clean();
                }
            }
            elseif (isset($_POST['email']) && !empty($_POST['email']))
            {
                $update = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->users} SET `user_email` = %s WHERE `ID` = %d", array($_POST['email'], $user_ID)));
                if (!is_wp_error($update)) {
                    wp_cache_delete($user->user_email, 'useremail');
                    $user_email = $_POST['email'];
                    ob_start();
?>
                <div id="message" class="updated fade">
                    <p><strong><?php _e('E-mail Address updated.') ?></strong></p>
                </div>
<?php
                    $_POST['post_msg'] = ob_get_clean();
                }
                if (is_wp_error($update)) {
                    ob_start();
?>
                <div class="error">
                    <ul>
<?php
                    foreach ($update->get_error_messages() as $message) {
?>
                        <li><?php echo $message; ?></li>
<?php
                    }
?>
                    </ul>
                </div>
<?php
                    $_POST['post_msg'] = ob_get_clean();
                }
            }
        }
    }
}

function change_password_email_pw () {
    global $wpdb, $user_ID;
    $title = __('Change Password');
    $what = 'change-password';
    $user = get_userdata($user_ID);
    if (isset($_POST['post_msg']))
        echo $_POST['post_msg'];
?>
<div class="wrap" id="profile-page">
    <?php screen_icon(); ?>
    <h2><?php echo esc_html($title); ?></h2>
    <form id="your-profile" action="" method="post">
        <table class="form-table">
            <tr id="password">
                <th><label for="pass1"><?php _e('New Password'); ?></label></th>
                <td><input type="password" name="pass1" id="pass1" size="16" value="" autocomplete="off" />
                    <span class="description"><?php _e("If you would like to change the password type a new one. Otherwise leave this blank."); ?></span><br />
                    <input type="password" name="pass2" id="pass2" size="16" value="" autocomplete="off" />
                    <span class="description"><?php _e("Type your new password again."); ?></span><br />

                    <div id="pass-strength-result"><?php _e('Strength indicator'); ?></div>
                    <p class="description indicator-hint"><?php _e('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).'); ?></p>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="hidden" name="user_login" id="user_login" value="<?php echo $user->user_login; ?>" />
            <input type="submit" class="button-primary" value="<?php esc_attr_e('Update Password') ?>" name="submit" />
        </p>
    </form>
</div>
<?php
}

function change_password_email_email () {
    global $wpdb, $user_ID, $user_email;
    $title = __('Change E-mail');
    $what = 'change-email';
    if (isset($_POST['post_msg']))
        echo $_POST['post_msg'];
?>
<div class="wrap" id="profile-page">
    <?php screen_icon(); ?>
    <h2><?php echo esc_html($title); ?></h2>
    <form id="your-profile" action="" method="post">
        <table class="form-table">
            <tr>
                <th><label for="email"><?php _e('E-mail Address'); ?></label></th>
                <td>
                    <input type="text" name="email" id="email" value="<?php echo esc_attr($user_email) ?>" class="regular-text" />
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php esc_attr_e('Update E-mail Address') ?>" name="submit" />
        </p>
    </form>
</div>
<?php
}