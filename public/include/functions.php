<?php
function _t($key, $return = false)
{
    if($return)
    {
        return $key;
    }
    else
    {
        echo $key;
    }
}

function isAjax()
{
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}

function selectElement($data, $attributes=array(), $firstOption='', $selectedKey='')
{
    $attrString = '';
    foreach($attributes as $attributeKey=>$attributeValue)
    {
        $attrString.= " ".$attributeKey.'="'.$attributeValue.'"';

    }
    $select = '<select'.$attrString.'>';
    if ($firstOption) {
        $select.='<option value="0">'.$firstOption.'</option>';
    }
    foreach($data as $key=>$val)
    {
        $selected = $selectedKey==$key?' selected="selected"':'';
        $select.='<option value="'.$key.'"'.$selected.'>'.$val.'</option>';
    }
    $select.='</select>';
    return $select;
}

function filterStatusesByActionType($currentBackup, $currentOptions)
{
    $filteredStatuses = array();
    if($currentBackup['type'] == SG_ACTION_TYPE_RESTORE)
    {
        $filteredStatuses[] = SG_ACTION_TYPE_RESTORE.SG_ACTION_STATUS_IN_PROGRESS_FILES;
        $filteredStatuses[] = SG_ACTION_TYPE_RESTORE.SG_ACTION_STATUS_IN_PROGRESS_DB;
    }
    else
    {
        $currentOptions = activeOptionToType($currentOptions);
        if ($currentOptions['database']) $filteredStatuses[] = $currentOptions['database'];
        if ($currentOptions['files']) $filteredStatuses[] = $currentOptions['files'];
        if ($currentOptions['ftp']) $filteredStatuses[] = $currentOptions['ftp'];
        if ($currentOptions['dropbox']) $filteredStatuses[] = $currentOptions['dropbox'];
        if ($currentOptions['gdrive']) $filteredStatuses[] = $currentOptions['gdrive'];
    }
    return $filteredStatuses;
}

function activeOptionToType($activeOption)
{
    $activeOption['database'] = $activeOption['database']?SG_ACTION_STATUS_IN_PROGRESS_DB:0;
    $activeOption['files'] = $activeOption['files']?SG_ACTION_STATUS_IN_PROGRESS_FILES:0;
    $activeOption['ftp'] = $activeOption['ftp']?SG_ACTION_TYPE_UPLOAD.SG_STORAGE_FTP:0;
    $activeOption['dropbox'] = $activeOption['dropbox']?SG_ACTION_TYPE_UPLOAD.SG_STORAGE_DROPBOX:0;
    $activeOption['gdrive'] = $activeOption['gdrive']?SG_ACTION_TYPE_UPLOAD.SG_STORAGE_GOOGLE_DRIVE:0;
    return $activeOption;
}

function convertToBytes($from){
    $number=substr($from,0,-2);
    switch(strtoupper(substr($from,-2))){
        case "KB":
            return $number*1024;
        case "MB":
            return $number*pow(1024,2);
        case "GB":
            return $number*pow(1024,3);
        case "TB":
            return $number*pow(1024,4);
        case "PB":
            return $number*pow(1024,5);
        default:
            return $from;
    }
}

function getRunningActions()
{
    $runningActions = SGBackup::getRunningActions();
    $isAnyActiveActions = count($runningActions);
    if($isAnyActiveActions)
    {
        $activeBackup = $runningActions[0];
        return $activeBackup;
    }
    return false;
}

function getReport()
{
    $jsonArray = array();
    $headerArray = array();
    $jsonArray['ptype'] = '1';
    $jsonArray['token'] = '28016ffb35b451291bfed7c5905474d6';
    $lastReportTime = SGConfig::get('SG_LAST_REPORT_DATE');
    if(!$lastReportTime)
    {
        SGConfig::set('SG_LAST_REPORT_DATE', time());
    }
    SGBackup::getLogFileHeader($headerArray);
    $jsonArray['header'] = $headerArray;
    $jsonArray['header']['uid'] = sha1("http://".@$_SERVER[HTTP_HOST].@$_SERVER[REQUEST_URI]);
    $sgdb = SGDatabase::getInstance();
    $res = $sgdb->query('SELECT type, subtype, status, count(*) AS scount FROM '.SG_ACTION_TABLE_NAME.' WHERE update_date > FROM_UNIXTIME(%d) GROUP BY type, subtype, status', array($lastReportTime));
    if(count($res))
    {
        foreach ($res as $item)
        {
            $jsonArray['log'][] = $item;
        }
    }
    return json_encode($jsonArray);
}