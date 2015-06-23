<?php

global $wp_version, $wpdb;
define('SG_ENV_VERSION', $wp_version);
define('SG_ENV_ADAPTER', 'Wordpress');
define('SG_ENV_DB_PREFIX', $wpdb->prefix);

require_once(dirname(__FILE__).'/config.php');

//Database
define('SG_DB_ADAPTER', SG_ENV_ADAPTER);
define('SG_DB_NAME', $wpdb->dbname);
define('SG_BACKUP_DATABASE_EXCLUDE', SG_ACTION_TABLE_NAME.','.SG_CONFIG_TABLE_NAME);

//Mail
define('SG_MAIL_TEMPLATES_PATH', SG_APP_PATH.'../public/templates/'); //fix this after development
define('SG_MAIL_BACKUP_TEMPLATE', 'mail_backup.php'); //fix this after development
define('SG_MAIL_RESTORE_TEMPLATE', 'mail_restore.php'); //fix this after development

//Backup
$wpContent = basename(WP_CONTENT_DIR);
$wpPlugins = basename(WP_PLUGIN_DIR);
$upload_dir = wp_upload_dir();
$wpUploads = basename($upload_dir['basedir']);
$wpThemes = basename(get_theme_root());

define('SG_APP_ROOT_DIRECTORY', ABSPATH); //Wordpress Define
define('SG_BACKUP_FILE_PATHS_EXCLUDE', $wpContent.'/'.$wpPlugins.'/backup-guard/,'.$wpContent.'/'.$wpUploads.'/backup-guard/');
define('SG_BACKUP_DIRECTORY', $upload_dir['basedir'].'/backup-guard/'); //backups will be stored here

//Storage
define('SG_STORAGE_UPLOAD_CRON', '');

define('SG_BACKUP_FILE_PATHS', $wpContent.','.$wpContent.'/'.$wpPlugins.','.$wpContent.'/'.$wpThemes.','.$wpContent.'/'.$wpUploads);