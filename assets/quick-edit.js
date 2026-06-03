(function($){
    'use strict';

    var qeNonce = window.wptwQuickEdit ? window.wptwQuickEdit.nonce : '';

    $(document).on('click', '.editinline', function(){
        var postId = $(this).closest('tr').attr('id').replace('post-', '');
        var $row = $('#post-' + postId);

        var disable = $row.find('.wptw-qe-data').data('disable') || 0;
        var state = $row.find('.wptw-qe-data').data('state') || '';
        var position = $row.find('.wptw-qe-data').data('position') || '';
        var nums = $row.find('.wptw-qe-data').data('nums');
        if (nums === undefined) nums = '';

        setTimeout(function(){
            var $qe = $('.inline-edit-row:visible');
            $qe.find('[name="wptw_qe[disable]"]').prop('checked', !!parseInt(disable, 10));
            $qe.find('[name="wptw_qe[default_state]"]').val(state);
            $qe.find('[name="wptw_qe[position]"]').val(position);
            $qe.find('[name="wptw_qe[show_numbers]"]').val(String(nums));
            $qe.find('[name="wptw_qe_nonce"]').val(qeNonce);
            $qe.find('[name="wptw_qe_post_id"]').val(postId);
        }, 50);
    });

    $(document).on('click', '.save.button', function(){
        var $qe = $(this).closest('.inline-edit-row');
        var postId = $qe.find('[name="wptw_qe_post_id"]').val();
        var nonce = $qe.find('[name="wptw_qe_nonce"]').val();
        if (!postId || !nonce) return;

        $.post(ajaxurl, {
            action: 'wptw_quick_edit_save',
            post_id: postId,
            nonce: nonce,
            disable: $qe.find('[name="wptw_qe[disable]"]').is(':checked') ? 1 : 0,
            default_state: $qe.find('[name="wptw_qe[default_state]"]').val(),
            position: $qe.find('[name="wptw_qe[position]"]').val(),
            show_numbers: $qe.find('[name="wptw_qe[show_numbers]"]').val()
        });
    });
})(jQuery);
