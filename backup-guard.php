<?php

/**
 * Plugin Name:       Backup Guard
 * Plugin URI:        https://backup-guard.com/products/backup-wordpress
 * Description:       Backup Guard for WordPress is the best backup choice for WordPress based websites or blogs.
 * Version:           1.0.0
 * Author:            Backup Guard
 * Author URI:        https://backup-guard.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

require_once plugin_dir_path( __FILE__ ).'public/boot.php';

/**
 * The code that runs during plugin activation.
 */
function activate_backup_guard() {
    SGBoot::install();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_backup_guard() {
    SGBoot::uninstall();
}

register_activation_hook( __FILE__, 'activate_backup_guard' );
register_deactivation_hook( __FILE__, 'deactivate_backup_guard' );

// Register Admin Menus for single and multisite
if(is_multisite())
{
    add_action('network_admin_menu', 'backup_guard_admin_menu');
}
else
{
    add_action('admin_menu', 'backup_guard_admin_menu');
}

function backup_guard_admin_menu() {
    add_menu_page('Backups', 'Backup Guard', 'manage_options', 'backup_guard_backups', 'backup_guard_backups_page', 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHZpZXdCb3g9IjAgMCA0NjIuOSA1MDEuNCIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgNDYyLjkgNTAxLjQiIHhtbDpzcGFjZT0icHJlc2VydmUiPjxwYXRoIGZpbGw9IiNhMGE1YWEiIGQ9Ik00MjYuOSwxOTkuNmgtMTk4bDAuNCwzNEgyNDZoMTYyLjdjLTAuNSwzLjMtMS4xLDYuNi0xLjcsOS45Yy02LjEsMzMtMTUuMyw2Mi4yLTI3LjcsODcuNkg3OS40Yy0xMi4zLTI1LjQtMjEuNi01NC42LTI3LjctODcuNkMzOS4zLDE3Ni4xLDQ0LDExMS41LDQ3LjIsODMuN0M2Ny43LDkwLjUsODguMyw5NCwxMDguNiw5NGM2MC43LDAsMTAzLjMtMzAuMiwxMjAuOC00NS4xQzI0Ni43LDYzLjgsMjg5LjQsOTQsMzUwLjEsOTRoMGMyMC4zLDAsNDAuOS0zLjUsNjEuNC0xMC4zYzEuNiwxMy45LDMuNSwzNy4xLDMuNiw2NS4xaDIzLjdjMC00Ny40LTUuNS04MS4xLTUuOC04My4zbC0yLjQtMTQuNmwtMTMuNyw1LjZjLTIyLjQsOS4yLTQ0LjgsMTMuOC02Ni43LDEzLjhjMCwwLDAsMCwwLDBjLTY4LjMsMC0xMTEuNy00NS4zLTExMi4xLTQ1LjdsLTguNi05LjJsLTguNyw5LjJjLTAuNCwwLjUtNDMuOCw0NS43LTExMi4xLDQ1LjdjLTIxLjksMC00NC40LTQuNi02Ni43LTEzLjhsLTEzLjctNS42bC0yLjQsMTQuNmMtMC42LDMuNi0xNC40LDg4LjcsMi42LDE4MS42QzM4LjUsMzAyLjQsNTcuNSwzNDguOCw4NC44LDM4NWMzNC42LDQ1LjgsODIuNCw3NS4zLDE0Mi4xLDg3LjdsMi40LDAuNWwyLjQtMC41YzU5LjctMTIuMywxMDcuNS00MS44LDE0Mi4xLTg3LjdjMjcuNC0zNi4zLDQ2LjQtODIuNyw1Ni41LTEzNy45YzMtMTYuMiw1LTMyLjIsNi4zLTQ3LjVMNDI2LjksMTk5LjZMNDI2LjksMTk5LjZ6Ii8+PC9zdmc+', 71);
    add_submenu_page( 'backup_guard_backups', 'Backups', 'Backups', 'manage_options', 'backup_guard_backups', 'backup_guard_backups_page');
    if(SGBoot::isFeatureAvailable('STORAGE'))
    {
        add_submenu_page('backup_guard_backups', 'Cloud', 'Cloud', 'manage_options', 'backup_guard_cloud', 'backup_guard_cloud_page');
    }
    if(SGBoot::isFeatureAvailable('SCHEDULE'))
    {
        add_submenu_page('backup_guard_backups', 'Schedule', 'Schedule', 'manage_options', 'backup_guard_schedule', 'backup_guard_schedule_page');
    }
    if(SGBoot::isFeatureAvailable('NOTIFICATIONS'))
    {
        add_submenu_page('backup_guard_backups', 'Settings', 'Settings', 'manage_options', 'backup_guard_settings', 'backup_guard_settings_page');
    }
}

//Backups Page
function backup_guard_backups_page(){
    wp_enqueue_script('backup-guard-backups-js', plugin_dir_url( __FILE__ ).'public/js/sgbackup.js', array( 'jquery' ), '1.0.0', true );

    require_once(plugin_dir_path( __FILE__ ).'public/backups.php');
}

//Cloud Page
function backup_guard_cloud_page(){
    wp_enqueue_style('backup-guard-switch-css', plugin_dir_url( __FILE__ ).'public/css/bootstrap-switch.min.css');
    wp_enqueue_script('backup-guard-switch-js', plugin_dir_url( __FILE__ ).'public/js/bootstrap-switch.min.js', array( 'jquery' ), '1.0.0', true );
    wp_enqueue_script('backup-guard-cloud-js', plugin_dir_url( __FILE__ ).'public/js/sgcloud.js', array( 'jquery'), '1.0.0', true );

    require_once(plugin_dir_path( __FILE__ ).'public/cloud.php');
}

//Schedule Page
function backup_guard_schedule_page(){
    wp_enqueue_style('backup-guard-switch-css', plugin_dir_url( __FILE__ ).'public/css/bootstrap-switch.min.css');
    wp_enqueue_script('backup-guard-switch-js', plugin_dir_url( __FILE__ ).'public/js/bootstrap-switch.min.js', array( 'jquery' ), '1.0.0', true );
    wp_enqueue_script('backup-guard-schedule-js', plugin_dir_url( __FILE__ ).'public/js/sgschedule.js', array( 'jquery'), '1.0.0', true );

    require_once(plugin_dir_path( __FILE__ ).'public/schedule.php');
}

//Settings Page
function backup_guard_settings_page(){
    wp_enqueue_style('backup-guard-switch-css', plugin_dir_url( __FILE__ ).'public/css/bootstrap-switch.min.css');
    wp_enqueue_script('backup-guard-switch-js', plugin_dir_url( __FILE__ ).'public/js/bootstrap-switch.min.js', array( 'jquery' ), '1.0.0', true );
    wp_enqueue_script('backup-guard-settings-js', plugin_dir_url( __FILE__ ).'public/js/sgsettings.js', array( 'jquery'), '1.0.0', true );

    require_once(plugin_dir_path( __FILE__ ).'public/settings.php');
}

//Enqueue Backup Guard General Scripts and Styles
function backup_guard_less_loader($tag){
    return preg_replace("/='stylesheet' id='backup-guard-less-css'/", "='stylesheet/less'", $tag);
}

add_action( 'admin_enqueue_scripts', 'enqueue_backup_guard_scripts' );
function enqueue_backup_guard_scripts($hook) {

    if(!strpos($hook,'backup_guard')){
        return;
    }

    wp_enqueue_style('backup-guard-spinner', plugin_dir_url( __FILE__ ).'public/css/spinner.css');
    wp_enqueue_style('backup-guard-wordpress', plugin_dir_url( __FILE__ ).'public/css/bgstyle.wordpress.css');
    wp_enqueue_style('backup-guard-less', plugin_dir_url( __FILE__ ).'public/css/sgstyles.less');
    add_filter('style_loader_tag', 'backup_guard_less_loader');

    echo '<script type="text/javascript">sgBackup={};SG_AJAX_URL = "'.SG_PUBLIC_AJAX_URL.'";';
    echo 'function getAjaxUrl(url) {'.
        'if (url==="cloudDropbox" || url==="cloudGdrive") return "'.admin_url('admin-post.php?action=backup_guard_').'"+url;'.
        'return "'.admin_url('admin-ajax.php').'";}</script>';

    wp_enqueue_script('backup-guard-less-framework', plugin_dir_url( __FILE__ ).'public/js/less.min.js', array( 'jquery' ), '1.0.0', true );
    wp_enqueue_script('backup-guard-bootstrap-framework', plugin_dir_url( __FILE__ ).'public/js/bootstrap.min.js', array( 'jquery' ), '1.0.0', true );
    wp_enqueue_script('backup-guard-sgrequest-js', plugin_dir_url( __FILE__ ).'public/js/sgrequesthandler.js', array( 'jquery' ), '1.0.0', true );
    wp_enqueue_script('backup-guard-sgwprequest-js', plugin_dir_url( __FILE__ ).'public/js/sgrequesthandler.wordpress.js', array( 'jquery' ), '1.0.0', true );
    wp_enqueue_script('backup-guard-main-js', plugin_dir_url( __FILE__ ).'public/js/main.js', array( 'jquery' ), '1.0.0', true );
}

// adding actions to handle modal ajax requests
add_action( 'wp_ajax_backup_guard_modalManualBackup', 'backup_guard_get_manual_modal');
add_action( 'wp_ajax_backup_guard_modalImport', 'backup_guard_get_import_modal');
add_action( 'wp_ajax_backup_guard_modalFtpSettings', 'backup_guard_get_ftp_modal');
add_action( 'wp_ajax_backup_guard_modalPrivacy', 'backup_guard_get_privacy_modal');
add_action( 'wp_ajax_backup_guard_modalTerms', 'backup_guard_get_terms_modal');

function backup_guard_get_manual_modal(){
    require_once(SG_PUBLIC_AJAX_PATH.'modalManualBackup.php');
    exit();
}

function backup_guard_get_import_modal(){
    require_once(SG_PUBLIC_AJAX_PATH.'modalImport.php');
    exit();
}

function backup_guard_get_ftp_modal(){
    require_once(SG_PUBLIC_AJAX_PATH.'modalFtpSettings.php');
    exit();
}

function backup_guard_get_privacy_modal(){
    require_once(SG_PUBLIC_AJAX_PATH.'modalPrivacy.php');
}

function backup_guard_get_terms_modal(){
    require_once(SG_PUBLIC_AJAX_PATH.'modalTerms.php');
    exit();
}

// adding actions to handle ajax and post requests
add_action( 'wp_ajax_backup_guard_cancelBackup', 'backup_guard_cancel_backup');
add_action( 'wp_ajax_backup_guard_checkBackupCreation', 'backup_guard_check_backup_creation');
add_action( 'wp_ajax_backup_guard_checkRestoreCreation', 'backup_guard_check_restore_creation');
add_action( 'admin_post_backup_guard_cloudDropbox', 'backup_guard_cloud_dropbox');
add_action( 'admin_post_backup_guard_cloudGdrive', 'backup_guard_cloud_gdrive');
add_action( 'wp_ajax_backup_guard_cloudDropbox', 'backup_guard_cloud_dropbox');
add_action( 'wp_ajax_backup_guard_cloudGdrive', 'backup_guard_cloud_gdrive');
add_action( 'wp_ajax_backup_guard_cloudFtp', 'backup_guard_cloud_ftp');
add_action( 'wp_ajax_backup_guard_curlChecker', 'backup_guard_curl_checker');
add_action( 'wp_ajax_backup_guard_deleteBackup', 'backup_guard_delete_backup');
add_action( 'admin_post_backup_guard_downloadBackup', 'backup_guard_download_backup');
add_action( 'wp_ajax_backup_guard_getAction', 'backup_guard_get_action');
add_action( 'wp_ajax_backup_guard_getRunningActions', 'backup_guard_get_running_actions');
add_action( 'wp_ajax_backup_guard_importBackup', 'backup_guard_get_import_backup');
add_action( 'wp_ajax_backup_guard_manualBackup', 'backup_guard_manual_backup');
add_action( 'wp_ajax_backup_guard_resetStatus', 'backup_guard_reset_status');
add_action( 'wp_ajax_backup_guard_restore', 'backup_guard_restore');
add_action( 'wp_ajax_backup_guard_saveCloudFolder', 'backup_guard_save_cloud_folder');
add_action( 'wp_ajax_backup_guard_schedule', 'backup_guard_schedule');
add_action( 'wp_ajax_backup_guard_settings', 'backup_guard_settings');
//action for schedule
add_action( 'backup_guard_schedule_action', 'backup_guard_scheduleAction');


function backup_guard_cancel_backup(){
    require_once(SG_PUBLIC_AJAX_PATH.'cancelBackup.php');
}

function backup_guard_check_backup_creation(){
    require_once(SG_PUBLIC_AJAX_PATH.'checkBackupCreation.php');
}

function backup_guard_check_restore_creation(){
    require_once(SG_PUBLIC_AJAX_PATH.'checkRestoreCreation.php');
}

function backup_guard_cloud_dropbox(){
    require_once(SG_PUBLIC_AJAX_PATH.'cloudDropbox.php');
}

function backup_guard_cloud_ftp(){
    require_once(SG_PUBLIC_AJAX_PATH.'cloudFtp.php');
}

function backup_guard_cloud_gdrive(){
    require_once(SG_PUBLIC_AJAX_PATH.'cloudGdrive.php');
}

function backup_guard_curl_checker(){
    require_once(SG_PUBLIC_AJAX_PATH.'curlChecker.php');
}

function backup_guard_delete_backup(){
    require_once(SG_PUBLIC_AJAX_PATH.'deleteBackup.php');
}

function backup_guard_download_backup(){
    require_once(SG_PUBLIC_AJAX_PATH.'downloadBackup.php');
}

function backup_guard_get_action(){
    require_once(SG_PUBLIC_AJAX_PATH.'getAction.php');
}

function backup_guard_get_running_actions(){
    require_once(SG_PUBLIC_AJAX_PATH.'getRunningActions.php');
}

function backup_guard_get_import_backup(){
    require_once(SG_PUBLIC_AJAX_PATH.'importBackup.php');
}

function backup_guard_manual_backup(){
    if(@in_array('wp-content', $_POST['directory'])){
        $_POST['directory'] = array('wp-content');
    }
    if($_POST['backupType'] == SG_BACKUP_TYPE_FULL){
        SGConfig::set('SG_BACKUP_FILE_PATHS', 'wp-content', false);
    }
    require_once(SG_PUBLIC_AJAX_PATH.'manualBackup.php');
}

function backup_guard_reset_status(){
    require_once(SG_PUBLIC_AJAX_PATH.'resetStatus.php');
}

function backup_guard_restore(){
    require_once(SG_PUBLIC_AJAX_PATH.'restore.php');
}

function backup_guard_save_cloud_folder(){
    require_once(SG_PUBLIC_AJAX_PATH.'saveCloudFolder.php');
}

function backup_guard_schedule(){
    if(@in_array('wp-content', $_POST['directory'])){
        $_POST['directory'] = array('wp-content');
    }
    if($_POST['backupType'] == SG_BACKUP_TYPE_FULL){
        SGConfig::set('SG_BACKUP_FILE_PATHS', 'wp-content', false);
    }
    require_once(SG_PUBLIC_AJAX_PATH.'schedule.php');
}

function backup_guard_settings(){
    require_once(SG_PUBLIC_AJAX_PATH.'settings.php');
}

//schedule
function backup_guard_scheduleAction(){
    require_once(SG_PUBLIC_PATH.'cron/sg_backup.php');
}

//adds once weekly to the existing schedules.
add_filter( 'cron_schedules', 'backup_guard_cron_add_weekly' );
function backup_guard_cron_add_weekly( $schedules ) {
    $schedules['weekly'] = array(
        'interval' => 60*60*24*7,
        'display' => 'Once weekly'
    );
    return $schedules;
}

//adds once monthly to the existing schedules.
add_filter( 'cron_schedules', 'backup_guard_cron_add_monthly' );
function backup_guard_cron_add_monthly( $schedules ) {
    $schedules['monthly'] = array(
        'interval' => 60*60*24*30,
        'display' => 'Once monthly'
    );
    return $schedules;
}

//adds once yearly to the existing schedules.
add_filter( 'cron_schedules', 'backup_guard_cron_add_yearly' );
function backup_guard_cron_add_yearly( $schedules ) {
    $schedules['yearly'] = array(
        'interval' => 60*60*24*30*12,
        'display' => 'Once yearly'
    );
    return $schedules;
}