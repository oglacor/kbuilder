/**
 * K-Builder admin: media pickers, the house preview markers and the finish preview.
 */
(function ($) {
    'use strict';

    const settings = window.kbuilderAdmin;
    const i18n = settings.i18n;
    const STEP = 0.5; // Arrow-key nudge, in % of the canvas.

    $(function () {
        $('.kbuilder-media-field').each(function () {
            initMediaField($(this));
        });
        $('.kbuilder-preview').each(function () {
            initMarkers($(this));
        });
        initFinishForm($('.kbuilder-finish-form'));
    });

    // Media ------------------------------------------------------------------

    function initMediaField($field) {
        const kind = $field.data('kbuilder-media'); // image | texture
        let frame = null;

        $field.on('click', '.kbuilder-media-select', function (event) {
            event.preventDefault();
            if (!frame) {
                frame = wp.media({
                    title: kind === 'image' ? i18n.chooseImage : i18n.chooseTexture,
                    button: { text: i18n.use },
                    library: { type: 'image' },
                    multiple: false,
                });
                frame.on('select', function () {
                    setMedia($field, kind, frame.state().get('selection').first().toJSON());
                });
            }
            frame.open();
        });
        $field.on('click', '.kbuilder-media-remove', function (event) {
            event.preventDefault();
            setMedia($field, kind, null);
        });
    }

    function setMedia($field, kind, attachment) {
        $field.find('.kbuilder-media-id').val(attachment ? attachment.id : '');
        $field.find('.kbuilder-media-remove').prop('hidden', !attachment);

        if (kind === 'image') {
            $field.find('.kbuilder-media-image').attr('href', attachment ? attachment.url : '');
            $field.find('.kbuilder-preview').toggleClass('is-empty', !attachment);
            const ratio = settings.canvas.width / settings.canvas.height;
            const wrong = !!(attachment && attachment.width && Math.abs(attachment.width / attachment.height / ratio - 1) > 0.01);
            $field.find('.kbuilder-image-warning')
                .prop('hidden', !wrong)
                .text(wrong ? i18n.imageSize.replace('%1$s', attachment.width).replace('%2$s', attachment.height) : '');
        } else {
            const size = attachment && attachment.sizes && (attachment.sizes.medium || attachment.sizes.thumbnail);
            const url = attachment ? (size || attachment).url : '';
            $field.find('.kbuilder-texture-chip').css('background-image', url ? `url("${url}")` : '');
        }
    }

    // House markers ----------------------------------------------------------

    function initMarkers($preview) {
        const $box = $preview.closest('.kbuilder-house-design');

        $preview.find('.kbuilder-marker').each(function () {
            const $marker = $(this);
            const region = $marker.data('region');
            const $input = $preview.find(`input[name="kbuilder_hotspot[${region}]"]`);
            const $zone = $preview.find(`.kbuilder-preview-zone[data-region="${region}"]`);
            const $legend = $box.find(`.kbuilder-preview-legend li[data-region="${region}"]`);

            // Same spot the builder picks when no position is saved.
            const autoPlace = function () {
                const spot = $zone.length && window.kbuilderAutoHotspot ? window.kbuilderAutoHotspot($zone[0]) : null;
                move(spot || [50, 50], false);
            };
            const move = function (spot, custom) {
                const x = clamp(spot[0]);
                const y = clamp(spot[1]);
                $marker.css({ left: `${x}%`, top: `${y}%` });
                if (custom) {
                    $input.val(`${x},${y}`);
                }
                $legend.find('.kbuilder-marker-state').text($input.val() ? i18n.custom : i18n.auto);
                $legend.find('.kbuilder-marker-reset').prop('hidden', !$input.val());
            };

            if ($input.val()) {
                move($input.val().split(',').map(Number), false);
            } else {
                autoPlace();
            }

            $marker.on('pointerdown', function (event) {
                event.preventDefault();
                this.setPointerCapture(event.pointerId);
                $marker.addClass('is-dragging');
            });
            $marker.on('pointermove', function (event) {
                if (!this.hasPointerCapture(event.pointerId)) {
                    return;
                }
                const rect = $preview[0].getBoundingClientRect();
                move([(event.clientX - rect.left) / rect.width * 100, (event.clientY - rect.top) / rect.height * 100], true);
            });
            $marker.on('pointerup pointercancel', function () {
                $marker.removeClass('is-dragging');
            });
            $marker.on('keydown', function (event) {
                const keys = { ArrowLeft: [-STEP, 0], ArrowRight: [STEP, 0], ArrowUp: [0, -STEP], ArrowDown: [0, STEP] };
                if (!keys[event.key]) {
                    return;
                }
                event.preventDefault();
                const left = parseFloat($marker[0].style.left) || 50;
                const top = parseFloat($marker[0].style.top) || 50;
                move([left + keys[event.key][0], top + keys[event.key][1]], true);
            });
            $marker.add($legend).on('mouseenter mouseleave', function (event) {
                $zone.toggleClass('is-hover', event.type === 'mouseenter');
            });
            $legend.on('click', '.kbuilder-marker-reset', function () {
                $input.val('');
                autoPlace();
            });
        });
    }

    function clamp(value) {
        return Math.round(Math.min(Math.max(value, 0), 100) * 100) / 100;
    }

    // Finish preview -----------------------------------------------------------

    function initFinishForm($form) {
        if (!$form.length) {
            return;
        }
        const $hex = $form.find('.kbuilder-color');
        const refresh = function (color) {
            const opacity = Number($form.find('#kbuilder-finish-opacity').val()) / 100;
            const zones = $form.find('.kbuilder-finish-zones input:checked').map(function () {
                return this.value;
            }).get();
            $form.find('.kbuilder-opacity-value').text(`${Math.round(opacity * 100)}%`);
            $form.find('.kbuilder-finish-preview-zone').each(function () {
                $(this).css({
                    fill: (typeof color === 'string' && color) || $hex.val() || '#FFFFFF',
                    opacity: zones.indexOf($(this).data('region')) === -1 ? 0 : opacity,
                    'mix-blend-mode': $form.find('#kbuilder-finish-blend').val(),
                });
            });
        };

        $hex.wpColorPicker({
            change: function (event, ui) {
                refresh(ui.color.toString());
            },
            clear: function () {
                refresh();
            },
        });
        $form.on('input change', '#kbuilder-finish-opacity, #kbuilder-finish-blend, .kbuilder-finish-zones input', function () {
            refresh();
        });
        refresh();
    }
})(jQuery);
