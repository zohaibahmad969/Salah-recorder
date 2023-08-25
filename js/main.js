jQuery(document).ready(function($) {
    jQuery(document).ready(function($) {
        $('.mark-as-read').on('click', function() {
            var button = $(this);
            var salah = button.data('salah');
            
            // Show preloader
            var preloader = $('<img src="http://salahrecorder.test/wp-content/uploads/2023/08/loading-gif-png-5.gif" alt="Loading..." class="preloader">');
            button.append(preloader);

            $.ajax({
                type: 'POST',
                url: salah_recorder_ajax.ajax_url,
                data: {
                    action: 'mark_salah_as_read',
                    salah_name: salah
                },
                success: function(response) {
                    // Update button text upon success
                    button.html('Marked as Read');
                    button.addClass('success');
                }
            });
        });
    });
});