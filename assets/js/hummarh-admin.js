(function ($) {
    'use strict';

    function setPreview($field, url) {
        var $preview = $field.closest('.hcs-admin-media-field').find('.hcs-admin-media-preview');

        $preview.empty();

        if (url) {
            $('<img>', {
                src: url,
                alt: ''
            }).appendTo($preview);
        }
    }

    $(document).on('click', '.hcs-admin-upload', function () {
        var $button = $(this);
        var $field = $button.closest('.hcs-admin-media-row').find('.hcs-admin-media-url');
        var frame = wp.media({
            title: 'Seleccionar imagen',
            button: {
                text: 'Usar imagen'
            },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();

            $field.val(attachment.url).trigger('change');
            setPreview($field, attachment.url);
        });

        frame.open();
    });

    $(document).on('click', '.hcs-admin-clear', function () {
        var $field = $(this).closest('.hcs-admin-media-row').find('.hcs-admin-media-url');

        $field.val('').trigger('change');
        setPreview($field, '');
    });

    $(document).on('change', '.hcs-admin-media-url', function () {
        setPreview($(this), $(this).val());
    });
})(jQuery);
