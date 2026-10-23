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
            const sizes = (attachment && attachment.sizes) || {};
            const swatch = attachment ? (sizes.medium || sizes.thumbnail || attachment).url : '';
            $field.find('.kbuilder-texture-chip').css('background-image', swatch ? `url("${swatch}")` : '');
            // The HD version is what the preview paints with.
            $field.attr('data-url', attachment ? (sizes.large || attachment).url : '').trigger('kbuilder:texture');
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

    // Paints the finish being edited on the first house model, with the same
    // renderer as the builder, plus the lens close-up.
    function initFinishForm($form) {
        if (!$form.length) {
            return;
        }
        const $hex = $form.find('.kbuilder-color');
        const $texture = $form.find('[data-kbuilder-media="texture"]');
        const $preview = $form.find('.kbuilder-finish-preview');
        const LENS_ORDER = ['windows', 'doors', 'walls', 'roof'];
        let color = $hex.val();
        let scene = null;
        let token = 0;
        let timer = null;

        const sceneReady = function () {
            if (!scene) {
                scene = window.KBuilderRecolor.loadImage($preview.data('photo')).then(function (image) {
                    const result = new window.KBuilderRecolor.Scene(image, $preview.data('width'), $preview.data('height'));
                    $preview.find('g[data-region]').each(function () {
                        const shapes = $(this).find('path').map(function () {
                            const transform = this.transform.baseVal.consolidate();
                            return { d: this.getAttribute('d'), rule: this.getAttribute('fill-rule') || 'nonzero', matrix: transform ? transform.matrix : null };
                        }).get();
                        result.addZone($(this).data('region'), shapes, !!$(this).data('profile'));
                    });
                    return result;
                });
            }
            return scene;
        };

        const render = function () {
            const current = ++token;
            const finish = {
                hex: color || '#FFFFFF',
                texture: $texture.attr('data-url') || '',
                scale: Number($form.find('#kbuilder-finish-scale').val()),
                grain: $form.find('#kbuilder-finish-grain').val(),
                shading: Number($form.find('#kbuilder-finish-shading').val()) / 100,
            };
            const zones = $form.find('.kbuilder-finish-zones input:checked').map(function () {
                return this.value;
            }).get().filter(function (zone) {
                return $preview.find(`g[data-region="${zone}"]`).length;
            });
            const layers = zones.map(function (zone) {
                return { zone: zone, finish: $.extend({}, finish) };
            });
            const lensZone = LENS_ORDER.filter(function (zone) {
                return zones.indexOf(zone) !== -1;
            })[0];

            sceneReady().then(function (painter) {
                let lens = null;
                if (lensZone) {
                    const $zone = $preview.find(`g[data-region="${lensZone}"]`);
                    const hint = String($zone.data('hotspot') || '').split(',').map(Number);
                    const focus = painter.focus(lensZone, hint.length === 2 && !isNaN(hint[0]) ? [hint[0] / 100 * painter.width, hint[1] / 100 * painter.height] : null);
                    lens = focus ? painter.renderLens(layers, focus[0], focus[1], 72, 360) : null;
                }
                return Promise.all([painter.render(layers), lens]).then(function (images) {
                    if (current !== token) {
                        return;
                    }
                    $preview.find('.kbuilder-finish-preview-house')[0].getContext('2d').putImageData(images[0], 0, 0);
                    $preview.find('.kbuilder-finish-preview-lens').prop('hidden', !images[1]);
                    if (images[1]) {
                        $preview.find('.kbuilder-finish-preview-lens canvas')[0].getContext('2d').putImageData(images[1], 0, 0);
                        $preview.find('.kbuilder-finish-preview-zone').text($preview.find(`g[data-region="${lensZone}"]`).data('label'));
                    }
                });
            }).catch(function (error) {
                console.error('[kbuilder]', error);
            });
        };
        const refresh = function () {
            $form.find('output[for="kbuilder-finish-scale"]').text(`${$form.find('#kbuilder-finish-scale').val()} px`);
            $form.find('output[for="kbuilder-finish-shading"]').text(`${$form.find('#kbuilder-finish-shading').val()}%`);
            $form.find('.kbuilder-texture-setting').toggleClass('is-inactive', !$texture.attr('data-url'));
            if ($preview.length) {
                clearTimeout(timer);
                timer = setTimeout(render, 120);
            }
        };

        $hex.wpColorPicker({
            change: function (event, ui) {
                color = ui.color.toString();
                refresh();
            },
            clear: function () {
                color = '';
                refresh();
            },
        });
        $form.on('input change', '#kbuilder-finish-scale, #kbuilder-finish-grain, #kbuilder-finish-shading, .kbuilder-finish-zones input', refresh);
        $texture.on('kbuilder:texture', refresh);
        refresh();
    }
})(jQuery);
