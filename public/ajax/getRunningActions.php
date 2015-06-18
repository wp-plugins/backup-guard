<?php
    require_once(dirname(__FILE__).'/../boot.php');
    require_once(SG_BACKUP_PATH.'SGBackup.php');
    if(isAjax())
    {
        $runningActions = SGBackup::getRunningActions();
        $isAnyActiveActions = count($runningActions);
        if($isAnyActiveActions)
        {
            $activeBackup = $runningActions[0];
            die(json_encode($activeBackup));
        }
        die('0');
    }