<?php
    require_once(dirname(__FILE__).'/../boot.php');
    require_once(SG_BACKUP_PATH.'SGBackup.php');
    if(isAjax() && count($_POST))
    {
        $options = $_POST;
        $error = array();
        $success = array('success'=>1);
        $activeOptions = array('database'=>0,'files'=>0,'ftp'=>0, 'gdrive'=>0, 'dropbox'=>0, 'background'=>0);
        try
        {
            //If background mode
            $isBackgroundMode= isset($options['backgroundMode'])?1:0;
            SGConfig::set('SG_BACKUP_IN_BACKGROUND_MODE', $isBackgroundMode, false);
            $activeOptions['background'] = $isBackgroundMode;

            //If cloud backup
            if(isset($options['backupCloud']) && count($options['backupStorages']))
            {
                $clouds = $options['backupStorages'];
                SGConfig::set('SG_BACKUP_UPLOAD_TO_STORAGES', implode(',',$clouds), false);
                $activeOptions['gdrive'] = in_array(SG_STORAGE_GOOGLE_DRIVE, $options['backupStorages'])?1:0;
                $activeOptions['ftp'] = in_array(SG_STORAGE_FTP, $options['backupStorages'])?1:0;
                $activeOptions['dropbox'] = in_array(SG_STORAGE_DROPBOX, $options['backupStorages'])?1:0;
            }

            if ($options['backupType'] == SG_BACKUP_TYPE_FULL)
            {
                SGConfig::set('SG_ACTION_BACKUP_DATABASE_AVAILABLE', 1, false);
                SGConfig::set('SG_ACTION_BACKUP_FILES_AVAILABLE', 1, false);
                $activeOptions['database'] = 1;
                $activeOptions['files'] = 1;
            }
            else if ($options['backupType'] == SG_BACKUP_TYPE_CUSTOM)
            {
                //If database backup
                $isDatabaseBackup = empty($options['backupDatabase'])?0:1;
                SGConfig::set('SG_ACTION_BACKUP_DATABASE_AVAILABLE', $isDatabaseBackup, false);
                $activeOptions['database'] = $isDatabaseBackup;

                //If files backup
                if(isset($options['backupFiles']) && count($options['directory']))
                {
                    $directories = $options['directory'];
                    SGConfig::set('SG_ACTION_BACKUP_FILES_AVAILABLE', 1, false);
                    SGConfig::set('SG_BACKUP_FILE_PATHS', implode(',',$directories), false);
                    $activeOptions['files'] = 1;
                }
                else
                {
                    SGConfig::set('SG_ACTION_BACKUP_FILES_AVAILABLE', 0, false);
                    SGConfig::set('SG_BACKUP_FILE_PATHS', 0, false);
                }
            }
            SGConfig::set('SG_ACTIVE_BACKUP_OPTIONS',json_encode($activeOptions));
            $backup = new SGBackup();
            $backup->backup();
            die(json_encode($success));
        }
        catch(SGException $exception)
        {
            array_push($error, $exception->getMessage());
            die(json_encode($error));
        }
    }