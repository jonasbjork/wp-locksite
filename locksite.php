<?php
/**
 * @package Locksite
 * @version 0.2
 */
/*
Plugin Name: Lock Site
Plugin URI: http://jonasbjork.net/
Description: Lock a site for viewing while you are working on it.
Author: Jonas Björk <jonas.bjork@gmail.com>
Version: 0.2
Author URI: http://jonasbjork.net/
License: GPL2
*/

if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

require_once('locksite_table.php');

function locksite_init() {
}
add_action('init', 'locksite_init');

function locksite_get_remote_addr() {
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function locksite_admin_page() {
    global $blog_id;

    if (isset($_POST['submit_me'])) {
        $retrieved_nonce = $_REQUEST['_wpnonce'];
        if (!wp_verify_nonce($retrieved_nonce, 'locksite_update_settings' ) ) wp_die( 'Failed security check' );

        $new_config = array();
        foreach($_POST['site'] as $s) {
            if (is_numeric($s)) {
                array_push($new_config, $s);
            }
        }
        locksite_save_config($new_config);

        $locksite_message = $_POST['locksite_message'];
        locksite_save_message($locksite_message);


        $w = explode("\n", $_POST['whitelist']);
        $whitelist = array();
        foreach ($w as $entry) {
            array_push($whitelist, trim($entry));
        }
        locksite_save_whitelist($whitelist);

        locksite_notify_update('Configuration saved.');
    }
    echo '<div class="wrap">';
    echo "<h2>Locksite</h2>";
    echo "<p>This plugin allows you to lock a site. The wp-admin area will not be locked, so you can always unlock a site.</p>";

    $sites = wp_get_sites();
    $lsc = locksite_load_config();

    // Create the table and fill it with data.
    $lock_site_table = new LockSite_Table();
    $lock_site_table_data = array();
    if (count($sites) > 0) {
        foreach ($sites as $site) {
            $tmp = array( 'blog_id' => $site['blog_id'], 'domain' => $site['domain'], 'path' => $site['path']);
            array_push($lock_site_table_data, $tmp);
        }
        $lock_site_table->set_data($lock_site_table_data);
    }

    print '<form id="form-table" action="admin.php?page=locksite" method="post">';
    $lock_site_table->prepare_items();
    $lock_site_table->display();
    print '<div class="tablenav bottom">';
    print '<span class="displaying-num">' . count($sites) . ' sites</span>';
    print '</div>';

    $wl = locksite_load_whitelist();
    $whitelist = "";
    foreach ($wl as $entry) {
        $whitelist .= $entry . "\n";
    }
    print '<h3>Whitelist</h3>';
    print '<p>IP addresses you add here will have access to your locked sites. Add one IP address per row.</p>';
    print "<p><textarea rows='10' cols='40' name='whitelist'>{$whitelist}</textarea></p>";
    print '<input type="hidden" name="page" value="locksite" />';

    print '<h3>Action when locked</h3>';
    print '<p>When a site is locked, show this message:</p>';

    $lock_message = locksite_load_message();
    wp_editor($lock_message, "locksite_message", array('textarea_rows'=>12, 'editor_class'=>'locksite_message_class', 'media_buttons' => false));

    wp_nonce_field('locksite_update_settings');
    print '<p><input type="submit" class="button apply" name="submit_me" value="Update"/></p>';
    print '</form>';

    print '</div>';
}

function locksite_check_lock() {
    global $blog_id;

    // Access to wp-admin is always allowed
    if (is_admin()) {
        return true;
    }

    // If remote address is allowed..
    if (in_array(locksite_get_remote_addr(), locksite_load_whitelist())) {
        return true;
    }

    if (locksite_load_config() == false) {
        return true;
    }
    if (in_array($blog_id, locksite_load_config())) {
        $message = locksite_load_message();
        if ($message == false) {
            wp_die('<h2>Site is locked</h2><p>This site is locked.</p>');
        } else {
            wp_die($message);
        }
    }
}
add_action('plugins_loaded', 'locksite_check_lock');


function locksite_notify_update($message) {
    echo '<div class="updated"><p>' . $message . '</p></div>';
}

function locksite_load_message() {
    $message = get_site_option('locksite_message', false);
    if ($message != false) {
        return unserialize($message);
    } else {
        return false;
    }
}

function locksite_save_message($message) {
    $msg = locksite_load_message();
    $message = serialize($message);
    if ($msg == false) {
        add_site_option('locksite_message', $message);
    } else {
        update_site_option('locksite_message', $message);
    }
}

function locksite_load_whitelist() {
    $wl = get_site_option('locksite_whitelist', false);
    if ($wl != false) {
        return unserialize($wl);
    } else {
        return false;
    }
}

function locksite_save_whitelist($wl) {
    $lwl = locksite_load_whitelist();
    $wl = serialize($wl);
    if ($lwl == false) {
        $a = add_site_option('locksite_whitelist', $wl);
    } else {
        update_site_option('locksite_whitelist', $wl);
    }
}

function locksite_load_config() {
    $lsc = get_site_option('locksite_config', false);
    if ($lsc != false) {
        return unserialize($lsc);
    } else {
        return false;
    }
}

function locksite_save_config( $config ) {
    $lsc = locksite_load_config();
    $config = serialize($config);
    if ($lsc == false) {
        add_site_option('locksite_config', $config);
    } else {
        $u = update_site_option('locksite_config', $config);
    }
}

/* Admin menu */
function locksite_admin_menu() {
    add_menu_page('Locksite', 'Locksite', 'manage_sites', 'locksite', 'locksite_admin_page');
}
add_action( 'admin_menu', 'locksite_admin_menu' );


