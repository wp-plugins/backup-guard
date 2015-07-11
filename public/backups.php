<?php
require_once(dirname(__FILE__).'/boot.php');
require_once(SG_PUBLIC_INCLUDE_PATH.'/header.php');
require_once(SG_BACKUP_PATH.'SGBackup.php');
$backups = SGBackup::getAllBackups();
$downloadUrl = admin_url('admin-post.php?action=backup_guard_downloadBackup&');
?>
<?php require_once(SG_PUBLIC_INCLUDE_PATH.'sidebar.php'); ?>
<?php if(SGConfig::get('SG_REVIEW_POPUP_STATE') == SG_SHOW_REVIEW_POPUP): ?>
    <!--  Review Box  -->
    <a href="javascript:void(0)" id="sg-review" class="hidden" data-toggle="modal" data-modal-name="manual-review" data-remote="modalReview"></a>
    <script type="text/javascript">sgShowReview = 1;</script>
<?php endif; ?>
<div id="sg-content-wrapper">
    <div class="container-fluid">
        <fieldset>
            <legend><?php echo _t('Backups')?></legend>
            <a href="javascript:void(0)" id="sg-manual-backup" class="pull-left btn btn-success" data-toggle="modal" data-modal-name="manual-backup" data-remote="modalManualBackup"><i class="glyphicon glyphicon-play"></i> <?php _t('Perform manual backup')?></a>
            <a href="javascript:void(0)" id="sg-import" class="pull-right btn btn-primary" data-toggle="modal" data-modal-name="import"  data-remote="modalImport"><i class="glyphicon glyphicon-open"></i> <?php _t('Import')?></a>
            <div class="clearfix"></div><br/>
            <table class="table table-striped paginated sg-backup-table">
                <thead>
                <tr>
                    <th><?php _t('Filename')?></th>
                    <th><?php _t('Size')?></th>
                    <th><?php _t('Date')?></th>
                    <th><?php _t('Status')?></th>
                    <th><?php _t('Actions')?></th>
                </tr>
                </thead>
                <tbody>
                <?php if(empty($backups)):?>
                    <tr>
                        <td colspan="5"><?php _t('No backups found.')?></td>
                    </tr>
                <?php endif;?>
                <?php foreach($backups as $backup): ?>
                    <tr>
                        <td><?php echo $backup['name'] ?></td>
                        <td><?php echo !$backup['active']?$backup['size']:'' ?></td>
                        <td><?php echo $backup['date'] ?></td>
                        <td id="sg-status-tabe-data" <?php echo $backup['active']?'data-toggle="tooltip" data-placement="top" data-original-title="" data-container="#sg-wrapper"':''?>>
                            <?php if($backup['active']):
                                $activeOptions = json_decode(SGConfig::get('SG_ACTIVE_BACKUP_OPTIONS'), true);
                                $filteredStatuses = filterStatusesByActionType($backup, $activeOptions);
                                ?>
                                <input type="hidden" id="sg-active-action-id" value="<?php echo $backup['id']?>"/>
                                <?php foreach ($filteredStatuses as $statusCode): ?>
                                <span class="btn-xs sg-status-icon sg-status-<?php echo $statusCode ?>">&nbsp;</span>
                            <?php endforeach; ?>
                                <div class="progress sg-progress">
                                    <div class="progress-bar progress-bar-danger"></div>
                                </div>
                            <?php else: ?>
                                <?php if ($backup['backup_warning'] == 1 || $backup['restore_warning'] == 1): ?>
                                    <span class="glyphicon glyphicon-warning-sign btn-xs text-warning" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo $backup['backup_warning']?_t('Warnings found during backup',true):_t('Warnings found during restore',true)?>" data-container="#sg-wrapper"></span>
                                <?php elseif ($backup['backup_error'] == 1 || $backup['restore_error'] == 1): ?>
                                    <span class="glyphicon glyphicon-warning-sign btn-xs text-danger" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo $backup['backup_error']?_t('Errors found during backup',true):_t('Errors found during restore',true)?>" data-container="#sg-wrapper"></span>
                                <?php else: ?>
                                    <span class="glyphicon glyphicon-ok btn-xs text-success"></span>
                                <?php endif;?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($backup['active']): ?>
                                <a class="btn btn-danger btn-xs sg-cancel-backup" href="javascript:void(0)" title="<?php _t('Stop')?>">&nbsp;<i class="glyphicon glyphicon-stop" aria-hidden="true"></i>&nbsp;</a>
                            <?php else: ?>
                                <a href="javascript:void(0)" data-sgbackup-name="<?php echo $backup['name'];?>" data-remote="deleteBackup" class="btn btn-danger btn-xs sg-remove-backup" title="<?php _t('Delete')?>">&nbsp;<i class="glyphicon glyphicon-remove" aria-hidden="true"></i>&nbsp;</a>
                                <div class="btn-group">
                                    <a href="javascript:void(0)" class="btn btn-primary dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false" title="<?php _t('Download')?>">
                                        &nbsp;<i class="glyphicon glyphicon-download-alt" aria-hidden="true"></i>&nbsp;
                                        <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <?php if($backup['files']):?>
                                            <li>
                                                <a href="<?php echo $downloadUrl.'backupName='.$backup['name'].'&downloadType='.SG_BACKUP_DWONLOAD_TYPE_SGBP ?>">
                                                    <i class="glyphicon glyphicon-hdd" aria-hidden="true"></i> <?php _t('Backup')?>
                                                </a>
                                            </li>
                                        <?php endif;?>
                                        <?php if($backup['backup_log']):?>
                                            <li>
                                                <a href="<?php echo $downloadUrl.'backupName='.$backup['name'].'&downloadType='.SG_BACKUP_DWONLOAD_TYPE_BACKUP_LOG ?>">
                                                    <i class="glyphicon glyphicon-list-alt" aria-hidden="true"></i> <?php _t('Backup log')?>
                                                </a>
                                            </li>
                                        <?php endif;?>
                                        <?php if($backup['restore_log']):?>
                                            <li>
                                                <a href="<?php echo $downloadUrl.'backupName='.$backup['name'].'&downloadType='.SG_BACKUP_DWONLOAD_TYPE_RESTORE_LOG ?>">
                                                    <i class="glyphicon glyphicon-th-list" aria-hidden="true"></i> <?php _t('Restore log')?>
                                                </a>
                                            </li>
                                        <?php endif;?>
                                    </ul>
                                </div>
                                <a href="javascript:void(0)" title="<?php _t('Restore')?>" class="btn btn-success btn-xs sg-restore" data-restore-name="<?php echo $backup['name']?>">
                                    &nbsp;<i class="glyphicon glyphicon-repeat" aria-hidden="true"></i>&nbsp;
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="text-right">
                <ul class="pagination"></ul>
            </div>
        </fieldset>
    </div>
    <?php require_once(SG_PUBLIC_INCLUDE_PATH.'/footer.php'); ?>
    <?php if((time() - SGConfig::get('SG_LAST_REPORT_DATE') > SG_REPORT_INTERVAL) && SGConfig::get('SG_SEND_ANONYMOUS_STATISTICS') !== '0'):
        $report = base64_encode(getReport()); SGConfig::set('SG_LAST_REPORT_DATE', time()); ?>
        <script type="text/javascript">jQuery.post('https://backup-guard.com/tms/api', {report: "<?php echo $report; ?>"});</script>
    <?php endif; ?>
</div>
<div class="clearfix"></div>