<?php
    $page = $_GET['page'];
?>
<div id="sg-sidebar-wrapper" class="metro">
    <nav class="sidebar dark">
        <ul>
            <li class="title"><a class="sg-site-url" target="_blank" href="<?php echo SG_BACKUP_SITE_URL;?>"></a></li>
            <li class="<?php echo strpos($page,'backups')?'active':''?>"><a href="<?php echo network_admin_url('admin.php?page=backup_guard_backups'); ?>"><span class="glyphicon glyphicon-hdd"></span>Backups</a></li>
            <?php if(SGBoot::isFeatureAvailable('STORAGE')): ?>
                <li class="<?php echo strpos($page,'cloud')?'active':''?>"><a href="<?php echo network_admin_url('admin.php?page=backup_guard_cloud'); ?>"><span class="glyphicon glyphicon-cloud" aria-hidden="true"></span>Cloud</a></li>
            <?php endif; ?>
            <?php if(SGBoot::isFeatureAvailable('SCHEDULE')): ?>
                <li class="<?php echo strpos($page,'schedule')?'active':''?>"><a href="<?php echo network_admin_url('admin.php?page=backup_guard_schedule'); ?>"><span class="glyphicon glyphicon-time" aria-hidden="true"></span>Schedule</a></li>
            <?php endif; ?>
            <li class="<?php echo strpos($page,'settings')?'active':''?>"><a href="<?php echo network_admin_url('admin.php?page=backup_guard_settings'); ?>"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span>Settings</a></li>
        </ul>
    </nav>
        <?php if(!SGBoot::isFeatureAvailable('SCHEDULE')): ?>
        <div class="sg-alert-pro">
            <p><?php _t('Backup to cloud, automatization, mail notifications, and more in our PRO package!'); ?></p>
            <p><a class="btn btn-primary" target="_blank" href="<?php echo SG_BACKUP_SITE_URL; ?>"><?php _t('Buy now!'); ?></a></p>
        </div>
    <?php endif; ?>
</div>