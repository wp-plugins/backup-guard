jQuery(document).on('change', '.btn-file :file', function() {
    var input = jQuery(this),
        numFiles = input.get(0).files ? input.get(0).files.length : 1,
        label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
    input.trigger('fileselect', [numFiles, label]);
});

jQuery(document).ready( function() {
    sgBackup.initTablePagination();
    sgBackup.initRestore();
    sgBackup.initActiveAction();
    sgBackup.initBackupDeletion();
    jQuery('span[data-toggle=tooltip]').tooltip();
});

//SGManual Backup AJAX callback
sgBackup.manualBackup = function(){
    var error = [];
    //Validation
    jQuery('.alert').remove();
    if(jQuery('input[type=radio][name=backupType]:checked').val() == 2) {
        if(jQuery('.sg-custom-option:checked').length <= 0) {
            error.push('Please choose one option.');
        }
        //Check if any file is selected
        if(jQuery('input[type=checkbox][name=backupFiles]:checked').length > 0) {
            if(jQuery('.sg-custom-backup-files input:checkbox:checked').length <= 0) {
                error.push('Please choose one file.');
            }
        }
    }
    //Check if any cloud is selected
    if(jQuery('input[type=checkbox][name=backupCloud]:checked').length > 0) {
        if (jQuery('.sg-custom-backup-cloud input:checkbox:checked').length <= 0) {
            error.push('Please choose one cloud.');
        }
    }
    //If any error show it and abort ajax
    if(error.length){
        var sgAlert = sgBackup.alertGenerator(error, 'alert-danger');
        jQuery('#sg-modal .modal-header').prepend(sgAlert);
        return false;
    }

    //Before all disable buttons...
    jQuery('.alert').remove();
    jQuery('.modal-footer .btn-primary').attr('disabled','disabled');
    jQuery('.modal-footer .btn-primary').html('Backing Up...');

    //Reset Status
    var resetStatusHandler = new sgRequestHandler('resetStatus', {});
    resetStatusHandler.callback = function(response, error){
        var manualBackupForm = jQuery('#manualBackup');
        var manualBackupHandler = new sgRequestHandler('manualBackup',manualBackupForm.serialize());
        manualBackupHandler.dataIsObject = false;
        //If error
        if(typeof response.success === 'undefined') {
            var sgAlert = sgBackup.alertGenerator(response, 'alert-danger');
            jQuery('#sg-modal .modal-header').prepend(sgAlert);
            alert(response);
            location.reload();
            return false;
        }
        manualBackupHandler.run();
        sgBackup.checkBackupCreation();
    };
    resetStatusHandler.run();
};

//Init file upload
sgBackup.initFileUpload = function(){
    var isFileSelected = false;
    jQuery('.btn-file :file').off('fileselect').on('fileselect', function(event, numFiles, label){
        var input = jQuery(this).parents('.input-group').find(':text'),
            log = numFiles > 1 ? numFiles + ' files selected' : label;

        if( input.length ) {
            input.val(log);
            isFileSelected = true;
        } else {
            if( log ) alert(log);
        }
    });
    jQuery('#uploadSgbpFile').click(function(){
        jQuery('.alert').remove();
        if(!isFileSelected){
            var alert = sgBackup.alertGenerator('Please select a file.', 'alert-danger');
            jQuery('#sg-modal .modal-header').prepend(alert);
            return false;
        }

        var sguploadFile = new FormData(),
            url = jQuery(this).attr('data-remote'),
            sgAllowedFileSize = jQuery('.sg-backup-upload-input').attr('data-max-file-size'),
            sgFile = jQuery('input[name=sgbpFile]')[0].files[0];
        sguploadFile.append('sgbpFile', sgFile);
        if(sgFile.size > sgAllowedFileSize){
            var alert = sgBackup.alertGenerator('File is too large.', 'alert-danger');
            jQuery('#sg-modal .modal-header').prepend(alert);
            return false;
        }
        jQuery('#uploadSgbpFile').attr('disabled','disabled');
        jQuery('#uploadSgbpFile').html('Uploading please wait...');

        var ajaxHandler = new sgRequestHandler(url, sguploadFile, {
            contentType: false,
            cache: false,
            xhr: function() {  // Custom XMLHttpRequest
                var myXhr = jQuery.ajaxSettings.xhr();
                if(myXhr.upload){ // Check if upload property exists
                    myXhr.upload.addEventListener('progress',sgBackup.fileUploadProgress, false); // For handling the progress of the upload
                }
                return myXhr;
            },
            processData: false
        });

        ajaxHandler.callback = function(response, error){
            jQuery('.alert').remove();
            if(typeof response.success !== 'undefined'){
                location.reload();
            }
            else{
                //if error
                var alert = sgBackup.alertGenerator(response, 'alert-danger');
                jQuery('#sg-modal .modal-header').prepend(alert);

                jQuery('#uploadSgbpFile').removeAttr('disabled');
                jQuery('#uploadSgbpFile').html('Upload');
            }
        };
        SG_CURRENT_ACTIVE_AJAX = ajaxHandler.run();
    });
};

sgBackup.fileUploadProgress = function(e){
    if(e.lengthComputable){
        jQuery('#uploadSgbpFile').html('Uploading ('+ Math.round((e.loaded*100.0)/ e.total)+'%)');
    }
}

sgBackup.initTablePagination = function(){
    jQuery.fn.sgTablePagination = function(opts){
        var jQuerythis = this,
            defaults = {
                perPage: 7,
                showPrevNext: false,
                hidePageNumbers: false,
                pagerSelector: 'pagination'
            },
            settings = jQuery.extend(defaults, opts);

        var listElement = jQuerythis.children('tbody');
        var perPage = settings.perPage;
        var children = listElement.children();
        var pager = jQuery('.pager');

        if (typeof settings.childSelector!="undefined") {
            children = listElement.find(settings.childSelector);
        }

        if (typeof settings.pagerSelector!="undefined") {
            pager = jQuery(settings.pagerSelector);
        }

        var numItems = children.size();
        var numPages = Math.ceil(numItems/perPage);

        pager.data("curr",0);

        if (settings.showPrevNext){
            jQuery('<li><a href="#" class="prev_link">«</a></li>').appendTo(pager);
        }

        var curr = 0;
        while(numPages > curr && (settings.hidePageNumbers==false)){
            jQuery('<li><a href="#" class="page_link">'+(curr+1)+'</a></li>').appendTo(pager);
            curr++;
        }

        if(curr<=1){
            jQuery(settings.pagerSelector).parent('div').hide();
            jQuery('.page_link').hide();
        }

        if (settings.showPrevNext){
            jQuery('<li><a href="#" class="next_link">»</a></li>').appendTo(pager);
        }

        pager.find('.page_link:first').addClass('active');
        pager.find('.prev_link').hide();
        if (numPages<=1) {
            pager.find('.next_link').hide();
        }
        pager.children().eq(1).addClass("active");

        children.hide();
        children.slice(0, perPage).show();

        pager.find('li .page_link').click(function(){
            var clickedPage = jQuery(this).html().valueOf()-1;
            goTo(clickedPage,perPage);
            return false;
        });
        pager.find('li .prev_link').click(function(){
            previous();
            return false;
        });
        pager.find('li .next_link').click(function(){
            next();
            return false;
        });

        function previous(){
            var goToPage = parseInt(pager.data("curr")) - 1;
            goTo(goToPage);
        }

        function next(){
            goToPage = parseInt(pager.data("curr")) + 1;
            goTo(goToPage);
        }

        function goTo(page){
            var startAt = page * perPage,
                endOn = startAt + perPage;

            children.css('display','none').slice(startAt, endOn).show();

            if (page>=1) {
                pager.find('.prev_link').show();
            }
            else {
                pager.find('.prev_link').hide();
            }

            if (page<(numPages-1)) {
                pager.find('.next_link').show();
            }
            else {
                pager.find('.next_link').hide();
            }

            pager.data("curr",page);
            pager.children().removeClass("active");
            pager.children().eq(page+1).addClass("active");

        }
    };
    jQuery('table.paginated').sgTablePagination({pagerSelector:'.pagination',showPrevNext:true,hidePageNumbers:false,perPage:7});
};

sgBackup.checkBackupCreation = function(){
    var sgBackupCreationHandler = new sgRequestHandler('checkBackupCreation', {});
    sgBackupCreationHandler.dataType = 'html';
    sgBackupCreationHandler.callback = function(response){
        jQuery('#sg-modal').modal('hide');
        location.reload();
    };
    sgBackupCreationHandler.run();
};

sgBackup.checkRestoreCreation = function(){
    var sgRestoreCreationHandler = new sgRequestHandler('checkRestoreCreation', {});
    sgRestoreCreationHandler.dataType = 'html';
    sgRestoreCreationHandler.callback = function(response){
        location.reload();
    };
    sgRestoreCreationHandler.run();
};

sgBackup.initManulBackupRadioInputs = function(){
    jQuery('input[type=radio][name=backupType]').off('change').on('change', function(){
        jQuery('.sg-custom-backup').fadeToggle();
    });
    jQuery('input[type=checkbox][name=backupFiles], input[type=checkbox][name=backupCloud]').off('change').on('change', function(){
        var sgCheckBoxWrapper = jQuery(this).closest('.checkbox').find('.sg-checkbox');
        sgCheckBoxWrapper.fadeToggle();
        if(jQuery(this).attr('name') == 'backupFiles') {
            sgCheckBoxWrapper.find('input[type=checkbox]').attr('checked', 'checked');
        }
    });
};

sgBackup.initManualBackupTooltips = function(){
    jQuery('[for=cloud-ftp]').tooltip();
    jQuery('[for=cloud-dropbox]').tooltip();
    jQuery('[for=cloud-gdrive]').tooltip();
};

sgBackup.initRestore = function(){
    jQuery('.sg-restore').click(function(){
        var bname = jQuery(this).attr('data-restore-name');
        if (confirm('Are you sure?')){
            sgBackup.showAjaxSpinner('#sg-content-wrapper');
            var resetStatusHandler = new sgRequestHandler('resetStatus');
            resetStatusHandler.callback = function(response) {
                //If error
                if(typeof response.success === 'undefined') {
                    alert(response);
                    location.reload();
                    return false;
                }
                var restoreHandler = new sgRequestHandler('restore',{bname: bname});
                restoreHandler.run();
                sgBackup.checkRestoreCreation();
            };
            resetStatusHandler.run();
        }
    });
};

sgBackup.initActiveAction = function(){
    if(jQuery('#sg-active-action-id').length<=0){
        return;
    }
    SG_ACTIVE_ACTION_ID = jQuery('#sg-active-action-id').val();
    //Cancel Button
    jQuery('.sg-cancel-backup').click(function(){
        if (confirm('Are you sure?')) {
            var sgCancelHandler = new sgRequestHandler('cancelBackup', {actionId: SG_ACTIVE_ACTION_ID});
            sgCancelHandler.run();
        }
    });
    //GetProgress
    sgBackup.getActionProgress(SG_ACTIVE_ACTION_ID);
};

sgBackup.getActionProgress = function(actionId){
    var progressBar = jQuery('.sg-progress .progress-bar');
    var sgActionHandler = new sgRequestHandler('getAction', {actionId: actionId});
    //Init tooltip
    var statusTooltip = jQuery('td[data-toggle=tooltip]').tooltip();

    var sgRunningActionsHandler = new sgRequestHandler('getRunningActions', {});
    sgRunningActionsHandler.callback = function(response){
        if(response){
            SG_ACTIVE_ACTION_ID = response.id;
            sgActionHandler.data = {actionId: response.id};
            sgActionHandler.run();
        }
        else{
            jQuery('[class*=sg-status]').addClass('active');
            jQuery('.sg-progress').remove();
            jQuery('#sg-active-action-id').remove();
            location.reload();
        }
    };

    sgActionHandler.callback = function(response){
        if(response){
            sgBackup.disableUi();
            var progressInPercents = response.progress+'%';
            progressBar.width(progressInPercents);
            sgBackup.statusUpdate(statusTooltip, response, progressInPercents);
            sgActionHandler.run();
        }
        else{
            sgRunningActionsHandler.run();
        }
    };
    sgActionHandler.run();
};

sgBackup.statusUpdate = function(tooltip, response, progressInPercents){
    var tooltipText = '';
    if(response.type == '1'){
        var currentAction = 'Backup';
        if (response.status == '1') {
            tooltipText = currentAction + ' database - '+progressInPercents;
        }
        else if (response.status == '2') {
            tooltipText = currentAction + ' files - '+progressInPercents;
        }
        jQuery('.sg-status-'+response.status).prevAll('[class*=sg-status]').addClass('active');
    }
    else if(response.type == '2'){
        var currentAction = 'Restore';
        if (response.status == '1') {
            tooltipText = currentAction + ' database - '+progressInPercents;
        }
        else if (response.status == '2') {
            tooltipText = currentAction + ' files - '+progressInPercents;
        }
        jQuery('.sg-status-'+response.type+response.status).prevAll('[class*=sg-status]').addClass('active');
    }
    else if(response.type == '3'){
        var cloudIcon = jQuery('.sg-status-'+response.type+response.subtype);
        if(response.subtype == '1'){
            tooltipText = 'Uploading to FTP - '+progressInPercents;
        }
        else if(response.subtype == '2'){
            tooltipText = 'Uploading to Dropbox - '+progressInPercents;
        }
        else if(response.subtype == '3'){
            tooltipText = 'Uploading to Google Drive - '+progressInPercents;
        }
        cloudIcon.prevAll('[class*=sg-status]').addClass('active');
    }
    tooltip.attr('data-original-title',tooltipText);
};

sgBackup.disableUi = function(){
    jQuery('#sg-manual-backup').attr('disabled','disabled');
    jQuery('#sg-import').attr('disabled','disabled');
    jQuery('.sg-restore').attr('disabled','disabled');
};

sgBackup.enableUi = function(){
    jQuery('#sg-manual-backup').removeAttr('disabled');
    jQuery('#sg-import').removeAttr('disabled');
    jQuery('.sg-restore').removeAttr('disabled');
};

sgBackup.initBackupDeletion = function(){
    jQuery('.sg-remove-backup').click(function(){
        var btn = jQuery(this),
            url = btn.attr('data-remote'),
            backupName = btn.attr('data-sgbackup-name');
        if (confirm('Are you sure?')) {
            var ajaxHandler = new sgRequestHandler(url, {backupName: backupName});
            ajaxHandler.callback = function (response) {
                location.reload();
            };
            ajaxHandler.run();
        }
    });
};