<?php
function realFilesize($filename)
{
    $fp = fopen($filename, 'r');
    $return = false;
    if (is_resource($fp))
    {
        if (PHP_INT_SIZE < 8) // 32 bit
        {
            if (0 === fseek($fp, 0, SEEK_END))
            {
                $return = 0.0;
                $step = 0x7FFFFFFF;
                while ($step > 0)
                {
                    if (0 === fseek($fp, - $step, SEEK_CUR))
                    {
                        $return += floatval($step);
                    }
                    else
                    {
                        $step >>= 1;
                    }
                }
            }
        }
        else if (0 === fseek($fp, 0, SEEK_END)) // 64 bit
        {
            $return = ftell($fp);
        }
    }

    return $return;
}

function formattedDuration($startTs, $endTs)
{
    $unit = 'seconds';
    $duration = $endTs-$startTs;
    if ($duration>=60 && $duration<3600)
    {
        $duration /= 60.0;
        $unit = 'minutes';
    }
    else if ($duration>=3600)
    {
        $duration /= 3600.0;
        $unit = 'hours';
    }
    $duration = number_format($duration, 2, '.', '');

    return $duration.' '.$unit;
}

function deleteDirectory($dirName)
{
    $dirHandle = null;
    if (is_dir($dirName))
    {
        $dirHandle = opendir($dirName);
    }
           
    if (!$dirHandle)
    {
        return false;
    }

    while ($file = readdir($dirHandle))
    {
        if ($file != "." && $file != "..")
        {
            if (!is_dir($dirName."/".$file))
            {
                @unlink($dirName."/".$file);
            }
            else
            {
                deleteDirectory($dirName.'/'.$file);
            }
        }
    }

    closedir($dirHandle);
    return @rmdir($dirName);
}

function downloadFile($file, $type = 'application/octet-stream')
{
    // Make sure the files exists, otherwise we are wasting our time
    if (!file_exists($file))
    {
        header("HTTP/1.1 404 Not Found");
        exit;
    }

    $size = realFilesize($file);

    $name = basename($file);
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false);
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename="'.$name.'";');
    header('Content-Type: ' . $type);
    header('Content-Length: ' . $size);

    $chunkSize = 1024 * 1024 * 8;
    $handle = fopen($file, 'rb');
    while (!feof($handle))
    {
        $buffer = fread($handle, $chunkSize);
        echo $buffer;
        ob_flush();
        flush();
    }
    fclose($handle);

    exit;
}

function shutdownAction($actionId, $actionType, $filePath, $dbFilePath)
{
    $action = SGBackup::getAction($actionId);
    if ($action && ($action['status']==SG_ACTION_STATUS_IN_PROGRESS_DB || $action['status']==SG_ACTION_STATUS_IN_PROGRESS_FILES))
    {
        SGBackupLog::writeExceptionObject(new SGExceptionServerError('Execution time abort'));

        SGBackup::changeActionStatus($actionId, SG_ACTION_STATUS_ERROR);

        if ($actionType==SG_ACTION_TYPE_BACKUP)
        {
            @unlink($filePath);
            @unlink($dbFilePath);
        }
    }
}