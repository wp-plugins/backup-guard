<?php
    require_once(dirname(__FILE__).'/../boot.php');
    require_once(SG_BACKUP_PATH.'SGBackup.php');
    if(isAjax() && count($_POST))
    {
        $error = array();
        try
        {
            //Unset Active Options
            $activeOptions = array('database' => 0, 'files' => 0, 'ftp' => 0, 'gdrive' => 0, 'dropbox' => 0, 'background' => 0);
            SGConfig::set('SG_ACTIVE_BACKUP_OPTIONS', json_encode($activeOptions));

            //Getting Backup Name
            $backupName = $_POST['bname'];
            $backup = new SGBackup();
            $backup->restore($backupName);
        }
        catch(SGException $exception)
        {
            array_push($error, $exception->getMessage());
            die(json_encode($error));
        }
    }