<?php
require_once(SG_BACKUP_PATH.'SGIBackupDelegate.php');
require_once(SG_LIB_PATH.'SGArchive.php');

class SGBackupFiles implements SGArchiveDelegate
{
    private $rootDirectory = '';
    private $excludeFilePaths = array();
    private $filePath = '';
    private $sgbp = null;
    private $delegate = null;
    private $nextProgressUpdate = 0;
    private $totalBackupFilesCount = 0;
    private $currentBackupFileCount = 0;
    private $progressUpdateInterval = 0;
    private $warningsFound = false;
    private $dontExclude = array();

    public function __construct()
    {
        $this->rootDirectory = realpath(SGConfig::get('SG_APP_ROOT_DIRECTORY')).'/';
    }

    public function setDelegate(SGIBackupDelegate $delegate)
    {
        $this->delegate = $delegate;
    }

    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    public function addDontExclude($ex)
    {
        $this->dontExclude[] = $ex;
    }

    public function didFindWarnings()
    {
        return $this->warningsFound;
    }

    public function backup($filePath)
    {
        SGBackupLog::writeAction('backup files', SG_BACKUP_LOG_POS_START);

        $excludeFilePaths = SGConfig::get('SG_BACKUP_FILE_PATHS_EXCLUDE');
        $this->excludeFilePaths = explode(',', $excludeFilePaths);

        $this->filePath = $filePath;
        $backupItems = SGConfig::get('SG_BACKUP_FILE_PATHS');
        $allItems = explode(',', $backupItems);

        SGBackupLog::write('Backup files: '.$backupItems);

        $this->sgbp = new SGArchive($filePath, 'w');

        if (!is_writable($filePath))
        {
            throw new SGExceptionForbidden('Could not create backup file: '.$filePath);
        }

        $this->resetBackupProgress($allItems);
        $this->warningsFound = false;

        SGBackupLog::write('Number of files to backup: '.$this->totalBackupFilesCount);

        foreach ($allItems as $item)
        {
            SGBackupLog::writeAction('backup file: '.$item, SG_BACKUP_LOG_POS_START);

            $path = $this->rootDirectory.$item;
            $this->addFileToArchive($path);

            SGBackupLog::writeAction('backup file: '.$item, SG_BACKUP_LOG_POS_END);
        }

        $this->sgbp->finalize();

        SGBackupLog::writeAction('backup files', SG_BACKUP_LOG_POS_END);
    }

    public function restore($filePath)
    {
        SGBackupLog::writeAction('restore files', SG_BACKUP_LOG_POS_START);

        $this->filePath = $filePath;

        $this->resetRestoreProgress(dirname($filePath));
        $this->warningsFound = false;

        $this->extractArchive($filePath);

        SGBackupLog::writeAction('restore files', SG_BACKUP_LOG_POS_END);
    }

    private function extractArchive($filePath)
    {
        $restorePath = $this->rootDirectory;

        $sgbp = new SGArchive($filePath, 'r');
        $sgbp->setDelegate($this);
        $sgbp->extractTo($restorePath);
    }

    public function getCorrectCdrFilename($filename)
    {
        $backupsPath = $this->pathWithoutRootDirectory(realpath(SG_BACKUP_DIRECTORY));
        
        if (strpos($filename, $backupsPath)===0)
        {
            $newPath = dirname($this->pathWithoutRootDirectory(realpath($this->filePath)));
            $filename = substr(basename(trim($this->filePath)), 0, -4); //remove sgbp extension
            return $newPath.'/'.$filename.'sql';
        }

        return $filename;
    }

    public function didExtractFile($filePath)
    {
        //update progress
        $this->currentBackupFileCount++;
        $this->updateProgress();
    }

    public function didFindExtractError($error)
    {
        $this->warn($error);
    }

    public function didCountFilesInsideArchive($count)
    {
        $this->totalBackupFilesCount = $count;
        SGBackupLog::write('Number of files to restore: '.$count);
    }

    private function resetBackupProgress($allItems)
    {
        $this->currentBackupFileCount = 0;
        $this->progressUpdateInterval = SGConfig::get('SG_ACTION_PROGRESS_UPDATE_INTERVAL');

        //get number of files to backup
        $this->totalBackupFilesCount = $this->getTotalCountOfBackupFiles($allItems);
        $this->nextProgressUpdate = $this->progressUpdateInterval;
    }

    private function resetRestoreProgress($restorePath)
    {
        $this->currentBackupFileCount = 0;
        $this->progressUpdateInterval = SGConfig::get('SG_ACTION_PROGRESS_UPDATE_INTERVAL');
        $this->nextProgressUpdate = $this->progressUpdateInterval;
    }

    private function getTotalCountOfBackupFiles($allItems)
    {
        $totalCount = 0;

        foreach ($allItems as $item)
        {
            $path = $this->rootDirectory.$item;
            
            $count = 0;
            $this->numberOfFilesInDirectory($path, $count);

            $totalCount += $count;
        }

        return $totalCount;
    }

    private function pathWithoutRootDirectory($path)
    {
        return substr($path, strlen($this->rootDirectory));
    }

    private function shouldExcludeFile($path)
    {
        if (in_array($path, $this->dontExclude))
        {
            return false;
        }

        //get the name of the file/directory removing the root directory
        $file = $this->pathWithoutRootDirectory($path);

        //check if file/directory must be excluded
        foreach ($this->excludeFilePaths as $exPath)
        {
            if (strpos($file, $exPath)===0)
            {
                return true;
            }
        }

        return false;
    }

    private function numberOfFilesInDirectory($path, &$count = 0)
    {
        if ($this->shouldExcludeFile($path)) return;

        if (is_dir($path))
        {
            if ($handle = @opendir($path))
            {
                while (($file = readdir($handle)) !== false)
                {
                    if ($file === '.')
                    {
                        continue;
                    }
                    if ($file === '..')
                    {
                        continue;
                    }

                    $this->numberOfFilesInDirectory($path.'/'.$file, $count);
                }
                closedir($handle);
            }
        }
        else
        {
            if (is_readable($path))
            {
                $count++;
            }
        }
    }

    public function cancel()
    {
        @unlink($this->filePath);
    }

    private function addFileToArchive($path)
    {
        if ($this->shouldExcludeFile($path)) return;

        //check if it is a directory
        if (is_dir($path))
        {
            $this->backupDirectory($path);
            return;
        }

        //it is a file, try to add it to archive
        if (is_readable($path))
        {
            $file = substr($path, strlen($this->rootDirectory));
            $file = str_replace('\\', '/', $file);
            $this->sgbp->addFileFromPath($file, $path);
        }
        else
        {
            $this->warn('Could not read file (skipping): '.$path);
        }

        //update progress and check cancellation
        $this->currentBackupFileCount++;
        if ($this->updateProgress())
        {
            if ($this->delegate && $this->delegate->isCancelled())
            {
                return;
            }
        }

        if (SGBoot::isFeatureAvailable('BACKGROUND_MODE') && $this->delegate->isBackgroundMode())
        {
            SGBackgroundMode::next();
        }
    }

    private function warn($message)
    {
        $this->warningsFound = true;
        SGBackupLog::writeWarning($message);
    }

    private function backupDirectory($path)
    {
        if ($handle = @opendir($path))
        {
            $filesFound = false;
            while (($file = readdir($handle)) !== false)
            {
                if ($file === '.')
                {
                    continue;
                }
                if ($file === '..')
                {
                    continue;
                }

                $filesFound = true;
                $this->addFileToArchive($path.'/'.$file);
            }

            if (!$filesFound)
            {
                $file = substr($path, strlen($this->rootDirectory));
                $file = str_replace('\\', '/', $file);
                $this->sgbp->addFile($file.'/', ''); //create empty directory
            }

            closedir($handle);
        }
        else
        {
            $this->warn('Could not read directory (skipping): '.$path);
        }
    }

    private function updateProgress()
    {
        $progress = round($this->currentBackupFileCount*100.0/$this->totalBackupFilesCount);

        if ($progress>=$this->nextProgressUpdate)
        {
            $this->nextProgressUpdate += $this->progressUpdateInterval;

            if ($this->delegate)
            {
                $this->delegate->didUpdateProgress($progress);
            }

            return true;
        }

        return false;
    }
}