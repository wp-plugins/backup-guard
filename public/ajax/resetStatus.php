<?php
    require_once(dirname(__FILE__).'/../boot.php');
    require_once(SG_BACKUP_PATH.'SGBackup.php');
    if(isAjax())
    {
        $error = array();
        try
        {
            //Check if any running action
            $runningAction = getRunningActions();
            if ($runningAction) {
                throw new SGException(_t('There is already another process running.', true));
            }
            SGConfig::set('SG_RUNNING_ACTION', 0, true);
            die('{"success":1}');
        }
        catch(SGException $exception)
        {
            array_push($error, $exception->getMessage());
            die(json_encode($error));
        }
    }