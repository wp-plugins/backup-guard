sgBackup = {};
sgBackup.isModalOpen = false;
SG_CURRENT_ACTIVE_AJAX = '';

jQuery(window).load(function() {
    sgBackup.showReviewModal();
});

jQuery(document).ready( function() {
    sgBackup.init();
    if(typeof SG_AJAX_REQUEST_FREQUENCY === 'undefined'){
        SG_AJAX_REQUEST_FREQUENCY = 2000;
    }
});

//SG init
sgBackup.init = function(){
    sgBackup.initModals();
};

//SG Modal popup logic
sgBackup.initModals = function(){
    jQuery('[data-toggle="modal"][href], [data-toggle="modal"][data-remote]').off('click').on('click', function(e) {
        e.preventDefault();
        var btn = jQuery(this),
            url = btn.attr('data-remote'),
            modalName = btn.attr('data-modal-name'),
            modal = jQuery('#sg-modal');
        if( modal.length == 0 ) {
            modal = jQuery('' +
            '<div class="modal fade" id="sg-modal" tabindex="-1" role="dialog" aria-hidden="true"></div>' +
            '');
            body.append(modal);
        }
        sgBackup.showAjaxSpinner('#sg-content-wrapper');

        var ajaxHandler = new sgRequestHandler(url, {});
        ajaxHandler.type = 'GET';
        ajaxHandler.dataType = 'html';
        ajaxHandler.callback = function(data, error) {
            sgBackup.hideAjaxSpinner();
            if (error===false) {
                jQuery('#sg-modal').append(data);
            }
            modal.on('hide.bs.modal', function() {
                if(SG_CURRENT_ACTIVE_AJAX != '') {
                    if (!confirm('Are you sure you want to cancel?')) {
                        return false;
                    }
                    SG_CURRENT_ACTIVE_AJAX.abort();
                    SG_CURRENT_ACTIVE_AJAX = '';
                }
            });
            modal.one('hidden.bs.modal', function() {
                modal.html('');
            }).modal('show');
            sgBackup.didOpenModal(modalName);
        };
        ajaxHandler.run();
    });
};

sgBackup.didOpenModal = function(modalName){
    if(modalName == 'manual-backup'){
        sgBackup.initManulBackupRadioInputs();
        sgBackup.initManualBackupTooltips();
    }
    else if(modalName == 'import'){
        sgBackup.initFileUpload();
    }
    else if(modalName == 'ftp-settings'){
        jQuery('#sg-modal').on('hidden.bs.modal', function () {
            if(sgBackup.isFtpConnected != true) {
                jQuery('input[data-storage=ftp]').bootstrapSwitch('state', false);
            }
        })
    }
    else if(modalName == 'manual-review'){
        var action = 'setReviewPopupState';
        jQuery('#sgLeaveReview').click(function(){
            var reviewUrl = jQuery(this).attr('data-review-url');
            //Never show again
            var reviewState = 2;
            var ajaxHandler = new sgRequestHandler(action, {reviewState: reviewState});
            ajaxHandler.run();
            window.open(reviewUrl);
        });

        jQuery('#sgDontAskAgain').click(function(){
            //Never show again
            var reviewState = 2;
            var ajaxHandler = new sgRequestHandler(action, {reviewState: reviewState});
            ajaxHandler.run();
        });

        jQuery('#sgAskLater').click(function(){
            var reviewState = 0;
            var ajaxHandler = new sgRequestHandler(action, {reviewState: reviewState});
            ajaxHandler.run();
        });
    }
};

sgBackup.isAnyOpenModal = function(){
    return jQuery('#sg-modal').length;
};

sgBackup.alertGenerator = function(content, alertClass){
    var sgalert = '';
    sgalert+='<div class="alert alert-dismissible '+alertClass+'">';
    sgalert+='<button type="button" class="close" data-dismiss="alert">×</button>';
    if(jQuery.isArray(content)){
        jQuery.each(content, function(index, value) {
            sgalert+=value+'<br/>';
        });
    }
    else if(content != ''){
        sgalert+=content.replace('[','').replace(']','').replace('"','');
    }
    sgalert+='</div>';
    return sgalert;
};

sgBackup.scrollToElement = function(id){
    if(jQuery(id).position()){
        if(jQuery(id).position().top < jQuery(window).scrollTop()){
            //scroll up
            jQuery('html,body').animate({scrollTop:jQuery(id).position().top}, 1000);
        }
        else if(jQuery(id).position().top + jQuery(id).height() > jQuery(window).scrollTop() + (window.innerHeight || document.documentElement.clientHeight)){
            //scroll down
            jQuery('html,body').animate({scrollTop:jQuery(id).position().top - (window.innerHeight || document.documentElement.clientHeight) + jQuery(id).height() + 15}, 1000);
        }
    }
};

sgBackup.showAjaxSpinner = function(appendToElement){
    if(typeof appendToElement == 'undefined'){
        appendToElement = '#sg-wrapper';
    }
    jQuery('<div class="sg-spinner"></div>').appendTo(appendToElement);
};

sgBackup.hideAjaxSpinner = function(){
    jQuery('.sg-spinner').remove();
};

less.pageLoadFinished.then(
    function() {
        jQuery('#sg-wrapper').show();
        jQuery('.sg-spinner').remove();
    }
);

sgBackup.showReviewModal = function(){
    if(typeof sgShowReview != 'undefined') {
        jQuery('#sg-review').trigger("click");
    }
};