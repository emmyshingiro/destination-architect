(function ($) {
    'use strict';

    function nextIndex($repeater) {
        var indexes = [];

        $repeater.find('.wta-repeater-item input, .wta-repeater-item textarea').each(function () {
            var match = this.name.match(/\[(\d+)\]\[/);
            if (match) {
                indexes.push(parseInt(match[1], 10));
            }
        });

        return indexes.length ? Math.max.apply(Math, indexes) + 1 : 0;
    }

    $(document).on('click', '.wta-add-item', function () {
        var $repeater = $(this).closest('.wta-repeater');
        var template = $repeater.find('.wta-repeater-template').html();
        var index = nextIndex($repeater);

        $repeater.find('.wta-repeater-items').append(template.replace(/__INDEX__/g, index));
    });

    $(document).on('click', '.wta-remove-item', function () {
        $(this).closest('.wta-repeater-item').remove();
    });

    $(document).on('click', '.wta-select-image', function (event) {
        event.preventDefault();

        var $item = $(this).closest('.wta-repeater-item');
        var frame = wp.media({
            title: 'Choose an image',
            button: { text: 'Use this image' },
            library: { type: 'image' },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $item.find('.wta-image-id').val(attachment.id);
            $item.find('.wta-image-preview').html(
                $('<img>', { src: attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url, alt: '' })
            );
        });

        frame.open();
    });

    $(document).on('click', '.wta-clear-image', function (event) {
        event.preventDefault();
        var $item = $(this).closest('.wta-repeater-item');
        $item.find('.wta-image-id').val('');
        $item.find('.wta-image-preview').empty();
    });
}(jQuery));
