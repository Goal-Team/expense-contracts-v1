/*!
 * health_packages.js
 * Requires jQuery
 *
 * Handles dynamic add/remove of "inclusions[]" inputs used in the health_packages form.
 */

(function ($) {
    'use strict';

    $(function () {
        // Add new inclusion input
        $(document).on('click', '#add-inclusion', function (e) {
            e.preventDefault();

            var $container = $('#inclusions-list');
            var $wrapper = $('<div class="input-group mb-2 inclusion-item"></div>');

            var $input = $('<input/>', {
                type: 'text',
                name: 'inclusions[]',
                class: 'form-control',
                value: ''
            });

            var $btn = $('<button/>', {
                type: 'button',
                class: 'btn btn-outline-danger remove-inclusion',
                text: 'Remove'
            });

            $wrapper.append($input).append($btn);
            $container.append($wrapper);
        });

        // Remove inclusion input
        $(document).on('click', '.remove-inclusion', function (e) {
            e.preventDefault();
            $(this).closest('.inclusion-item').remove();
        });
    });
})(jQuery);