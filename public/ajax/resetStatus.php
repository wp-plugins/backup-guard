<?php
    require_once(dirname(__FILE__).'/../boot.php');
    require_once(SG_BACKUP_PATH.'SGBackup.php');
    if(isAjax())
    {
        SGConfig::set('SG_RUNNING_ACTION', 0, true);
        die('{"success":1}');
    }