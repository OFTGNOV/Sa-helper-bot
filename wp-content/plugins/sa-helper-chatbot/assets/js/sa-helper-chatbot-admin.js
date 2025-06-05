/**
 * Admin scripts for SA Helper Chatbot
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Tab functionality for knowledge base editor
        $('.sa-helper-knowledge-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            $('.sa-helper-knowledge-tabs .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.tab-content').removeClass('active').hide();
            $(target).addClass('active').show();
        });

        // Initialize the first tab
        $('.sa-helper-knowledge-tabs .nav-tab:first').click();
    });

})(jQuery);
