<?php
require_once(SG_CORE_PATH.'functions.php');
require_once(SG_DATABASE_PATH.'SGDatabase.php');
require_once(SG_EXCEPTION_PATH.'SGExceptionHandler.php');
require_once(SG_CORE_PATH.'SGConfig.php');
@include_once(SG_SCHEDULE_PATH.'SGSchedule.php');

class SGBoot
{
    public static function init()
    {
        //remove execution time limit
        set_time_limit(0);

        //set default exception handler
        SGExceptionHandler::init();

        //load all config variables from database
        SGConfig::getAll();

        try
        {
            //check minimum requirements
            self::checkMinimumRequirements();

            //prepare directory for backups
            self::prepare();
        }
        catch (SGException $exception)
        {
            die($exception);
        }
    }

    public static function install()
    {
        try
        {
            $sgdb = SGDatabase::getInstance();

            //create config table
            $res = $sgdb->query('DROP TABLE IF EXISTS `'.SG_CONFIG_TABLE_NAME.'`;');
            if ($res===false)
            {
                throw new SGExceptionDatabaseError('Could not execute query');
            }
            $res = $sgdb->query('CREATE TABLE `'.SG_CONFIG_TABLE_NAME.'` (
                                  `ckey` varchar(255) NOT NULL,
                                  `cvalue` varchar(255) NOT NULL,
                                  PRIMARY KEY (`ckey`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
            if ($res===false)
            {
                throw new SGExceptionDatabaseError('Could not execute query');
            }

            //populate config table
            $res = $sgdb->query("INSERT INTO `".SG_CONFIG_TABLE_NAME."` VALUES 
                                ('SG_BACKUP_SYNCHRONOUS_STORAGE_UPLOAD','1'),
                                ('SG_NOTIFICATIONS_ENABLED','0'),
                                ('SG_NOTIFICATIONS_EMAIL_ADDRESS',''),
                                ('SG_STORAGE_BACKUPS_FOLDER_NAME','sg_backups');");
            if ($res===false)
            {
                throw new SGExceptionDatabaseError('Could not execute query');
            }

            //create action table
            $res = $sgdb->query('DROP TABLE IF EXISTS `'.SG_ACTION_TABLE_NAME.'`;');
            if ($res===false)
            {
                throw new SGExceptionDatabaseError('Could not execute query');
            }
            $res = $sgdb->query("CREATE TABLE `".SG_ACTION_TABLE_NAME."` (
                                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                  `name` varchar(255) NOT NULL,
                                  `type` tinyint(3) unsigned NOT NULL,
                                  `subtype` tinyint(3) unsigned NOT NULL DEFAULT '0',
                                  `status` tinyint(3) unsigned NOT NULL,
                                  `progress` tinyint(3) unsigned NOT NULL DEFAULT '0',
                                  `start_date` datetime NOT NULL,
                                  `update_date` datetime DEFAULT NULL,
                                  PRIMARY KEY (`id`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
            if ($res===false)
            {
                throw new SGExceptionDatabaseError('Could not execute query');
            }

            //try to create cron job for storage uploadings
            /*if (self::isFeatureAvailable('SCHEDULE'))
            {
                if (SGSchedule::isCronAvailable() && SGSchedule::create(SG_STORAGE_UPLOAD_CRON))
                {
                    SGConfig::set('SG_BACKUP_SYNCHRONOUS_STORAGE_UPLOAD', 0);
                }
            }*/
        }
        catch (SGException $exception)
        {
            die($exception);
        }
    }

    public static function uninstall($deleteBackups = false)
    {
        try
        {
            $sgdb = SGDatabase::getInstance();

            //drop config table
            $res = $sgdb->query('DROP TABLE IF EXISTS `'.SG_CONFIG_TABLE_NAME.'`;');
            if ($res===false)
            {
                throw new SGExceptionDatabaseError('Could not execute query');
            }

            //drop action table
            $res = $sgdb->query('DROP TABLE IF EXISTS `'.SG_ACTION_TABLE_NAME.'`;');
            if ($res===false)
            {
                throw new SGExceptionDatabaseError('Could not execute query');
            }

            //delete directory of backups
            if ($deleteBackups)
            {
                $backupPath = SGConfig::get('SG_BACKUP_DIRECTORY');
                deleteDirectory($backupPath);
            }
        }
        catch (SGException $exception)
        {
            die($exception);
        }
    }

    public static function checkRequirement($requirement)
    {
        if ($requirement=='ftp' && !extension_loaded('ftp'))
        {
            throw new SGExceptionNotFound('FTP extension is not loaded.');
        }
        else if ($requirement=='curl' && !function_exists('curl_version'))
        {
            throw new SGExceptionNotFound('cURL extension is not loaded.');
        }
    }

    public static function isFeatureAvailable($feature)
    {
        return (SGConfig::get('SG_FEATURE_'.strtoupper($feature))===1?true:false);
    }

    private static function prepare()
    {
        $backupPath = SGConfig::get('SG_BACKUP_DIRECTORY');

        //create directory for backups
        if (!is_dir($backupPath))
        {
            if (!@mkdir($backupPath))
            {
                throw new SGExceptionMethodNotAllowed('Cannot create folder: '.$backupPath);
            }

            if (!@file_put_contents($backupPath.'.htaccess', 'deny from all'))
            {
                throw new SGExceptionMethodNotAllowed('Cannot create htaccess file');
            }
        }

        //check permissions of backups directory
        if (!is_writable($backupPath))
        {
            throw new SGExceptionForbidden('Permission denied. Directory is not writable: '.$backupPath);
        }
    }

    private static function checkMinimumRequirements()
    {
        //check PHP version
        if (version_compare(PHP_VERSION, '5.3.0', '<'))
        {
            throw new SGExceptionNotFound('PHP >=5.3.0 version required.');
        }

        //check ZLib library
        if (!function_exists('gzdeflate'))
        {
            throw new SGExceptionNotFound('ZLib extension is not loaded.');
        }
    }
}