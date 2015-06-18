<?php

require_once(dirname(__FILE__).'/config.php');
//Url
define('SG_PLUGIN_NAME', 'backup-guard');
define('SG_PUBLIC_URL', plugins_url().'/'.SG_PLUGIN_NAME.'/public/');
define('SG_PUBLIC_AJAX_URL', SG_PUBLIC_URL.'ajax/');
define('SG_CLOUD_REDIRECT_URL', admin_url('admin.php?page=backup_guard_cloud'));