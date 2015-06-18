<?php
    require_once(dirname(__FILE__).'/../boot.php');
    require_once(SG_BACKUP_PATH.'SGBackup.php');
    if(isset($_POST['backupName']))
    {
        $backupName = $_POST['backupName'];
        SGBackup::deleteBackup($backupName);
    }
    die('{"success":1}');