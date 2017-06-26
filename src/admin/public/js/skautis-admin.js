(function ($) {
    'use strict';

    var $modal = $('#TB_ajaxContent');
    $('.thickbox').on('click', function () {
        resizeModal();
        var $this = $(this);
        var userName = $this.parents('tr').find('.username').html(),
            nickName = $this.parents('tr').find('.nickname').html();
        if (nickName) {
            userName += ' (' + nickName + ')';
        }

        $('#connectUserToSkautisModal_username').html(userName);

        var $connectUserToSkautisModal_connectLink = $('#connectUserToSkautisModal_connectLink');
        $connectUserToSkautisModal_connectLink.attr('href', $connectUserToSkautisModal_connectLink.attr('href') + '%3FskautisUserId=' + $this.parents('tr').find('.skautisUserId').html())
    });

    $(window).on('resize', resizeModal);

    $('#connectUserToSkautisModal_select').on('change', function () {
        var $this = $(this),
            $connectUserToSkautisModal_connectLink = $('#connectUserToSkautisModal_connectLink');
        $connectUserToSkautisModal_connectLink.attr('href', $connectUserToSkautisModal_connectLink.attr('href') + '%3FwpUserId=' + $this.val());
    });

    function resizeModal() {
        setTimeout(function () {
            var $tbAjaxContent = jQuery('#TB_ajaxContent').find('.content'),
                $tbWindow = jQuery("#TB_window");
            var width = $tbAjaxContent.outerWidth(),
                height = $tbAjaxContent.outerHeight();

            $tbWindow.css("width", width);
            $tbWindow.css("height", height);
            $tbWindow.css("margin-left", -(parseInt((width) / 2)));
            $tbWindow.css("margin-top", -(parseInt((height) / 2)));
        }, 10);
    }

})(jQuery);