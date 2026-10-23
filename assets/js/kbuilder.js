/**
 * K-Builder front-end: finish picking, house switching, the lead form gate and PDF export.
 */
(function ($) {
    'use strict';

    const LEAD_STORAGE_KEY = 'kbuilder_lead';
    const PAGE = { width: 297, height: 210, margin: 14 }; // A4 landscape, mm
    const RED = '#e91e2f';
    const DARK = '#0d0d0d';
    const GREY = '#7a7a7a';
    const LINE = '#dcdcdc';
    const LOGO_PX = 1200;

    $(function () {
        $('.kbuilder').each(function () {
            initBuilder($(this));
        });
    });

    function initBuilder($root) {
        const config = $root.data('config');
        if (!config || !config.houses) {
            return;
        }
        const i18n = config.i18n;
        // The modal lives in <body> so theme transforms or overflow can't trap a position:fixed overlay.
        const $modal = $root.find('.kbuilder-modal').appendTo(document.body);
        const state = { house: config.house, region: null, selections: {}, busy: false };
        let lastFocus = null;

        $.each(config.houses, function (houseKey, house) {
            state.selections[houseKey] = {};
            $.each(house.regions, function (regionKey) {
                state.selections[houseKey][regionKey] = '';
            });
        });
        placeHotspots(state.house);
        scan(state.house);

        $root.on('click', '.kbuilder-module-head', function () {
            toggleRegion($(this).closest('.kbuilder-module').data('region'));
        });
        // Markers and the zones on the photo open their zone in the panel.
        $root.on('click', '.kbuilder-hotspot, .kbuilder-outline', function () {
            toggleRegion($(this).data('region'), true);
        });
        $root.on('mouseenter mouseleave', '.kbuilder-module-head', function (event) {
            const regionKey = $(this).closest('.kbuilder-module').data('region');
            houseEl(state.house).find(`.kbuilder-outline[data-region="${regionKey}"]`).toggleClass('is-hover', event.type === 'mouseenter');
        });
        $root.on('click', '.kbuilder-swatch', function () {
            const regionKey = $(this).closest('.kbuilder-module').data('region');
            state.selections[state.house][regionKey] = $(this).attr('data-finish');
            applyFinish(state.house, regionKey);
        });
        $root.on('click', '.kbuilder-model', function () {
            showHouse($(this).attr('data-house'));
        });
        $root.on('click', '.kbuilder-reset', reset);
        $root.on('mousemove', '.kbuilder-stage', trackPointer);
        $root.on('mouseleave', '.kbuilder-stage', function () {
            $(this).find('.kbuilder-coords-x, .kbuilder-coords-y').text('----');
        });
        $root.on('click', '.kbuilder-download', function () {
            const lead = storedLead();
            if (!config.hasForm || !$modal.length || lead) {
                downloadPdf(lead || {});
            } else {
                openModal();
            }
        });

        $modal.on('click', '[data-kbuilder-close]', closeModal);
        $(document).on('keydown', function (event) {
            if (event.key === 'Escape' && $modal.length && !$modal.prop('hidden')) {
                closeModal();
            }
        });

        // Fluent Forms dispatches this on document after a successful AJAX submission.
        document.addEventListener('fluentform_submission_success', function (event) {
            const form = event.detail && event.detail.form;
            if (!form || !$modal.length || !$modal[0].contains(form)) {
                return;
            }
            const lead = readLead(form);
            storeLead(lead);
            // The form's own success message stays visible while the PDF renders.
            downloadPdf(lead).then(closeModal);
        });

        function houseEl(houseKey) {
            return $root.find(`.kbuilder-house[data-house="${houseKey}"]`);
        }

        function modulesEl(houseKey) {
            return $root.find(`.kbuilder-modules[data-house="${houseKey}"]`);
        }

        function selectedFinish(houseKey, regionKey) {
            const id = state.selections[houseKey][regionKey];
            return (id && config.finishes[id]) || null;
        }

        function applyFinish(houseKey, regionKey) {
            const finish = selectedFinish(houseKey, regionKey);
            const id = state.selections[houseKey][regionKey];

            houseEl(houseKey).find(`.kbuilder-region[data-region="${regionKey}"]`).css(finish ? {
                fill: finish.hex,
                opacity: finish.opacity,
                'mix-blend-mode': finish.blend,
            } : { opacity: 0 });

            const $module = modulesEl(houseKey).find(`.kbuilder-module[data-region="${regionKey}"]`);
            let $chip = $();
            $module.find('.kbuilder-swatch').each(function () {
                const selected = $(this).attr('data-finish') === id;
                $(this).toggleClass('selected', selected).attr('aria-pressed', selected);
                if (selected) {
                    $chip = $(this).find('.kbuilder-chip');
                }
            });
            $module.find('.kbuilder-module-value .kbuilder-chip').replaceWith($chip.clone());
            $module.find('.kbuilder-module-finish').text(finish ? finish.name : i18n.original);
        }

        function toggleRegion(regionKey, open) {
            state.region = regionKey && (open || regionKey !== state.region) ? regionKey : null;

            modulesEl(state.house).find('.kbuilder-module').each(function () {
                const isOpen = $(this).data('region') === state.region;
                $(this).toggleClass('is-open', isOpen);
                $(this).find('.kbuilder-module-head').attr('aria-expanded', isOpen);
                $(this).find('.kbuilder-module-body').prop('hidden', !isOpen);
                if (isOpen && open) {
                    this.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
            houseEl(state.house).find('.kbuilder-outline, .kbuilder-hotspot').each(function () {
                $(this).toggleClass('is-active', $(this).data('region') === state.region);
            });
        }

        function showHouse(houseKey) {
            if (!config.houses[houseKey] || houseKey === state.house) {
                return;
            }
            toggleRegion(null);
            state.house = houseKey;
            $root.find('.kbuilder-house, .kbuilder-modules').each(function () {
                $(this).toggleClass('active', $(this).attr('data-house') === houseKey);
            });
            $root.find('.kbuilder-model').each(function () {
                const active = $(this).attr('data-house') === houseKey;
                $(this).toggleClass('active', active).attr('aria-pressed', active);
            });
            $root.find('.kbuilder-house-name').text(config.houses[houseKey].name);
            placeHotspots(houseKey);
            scan(houseKey);
        }

        function reset() {
            $.each(state.selections[state.house], function (regionKey) {
                state.selections[state.house][regionKey] = '';
                applyFinish(state.house, regionKey);
            });
            toggleRegion(null);
        }

        // Markers without a position from the admin; measured once their house is visible.
        function placeHotspots(houseKey) {
            const $house = houseEl(houseKey);
            $house.find('.kbuilder-hotspot[data-auto]').each(function () {
                const group = $house.find(`.kbuilder-outline[data-region="${$(this).data('region')}"]`)[0];
                const spot = group && window.kbuilderAutoHotspot ? window.kbuilderAutoHotspot(group) : null;
                if (spot) {
                    $(this).css({ left: `${spot[0]}%`, top: `${spot[1]}%` }).removeAttr('data-auto');
                }
            });
        }

        // Replays the scan line over the photo.
        function scan(houseKey) {
            const $stage = houseEl(houseKey).find('.kbuilder-stage').removeClass('is-scanning');
            if ($stage.length) {
                void $stage[0].offsetWidth;
                $stage.addClass('is-scanning');
            }
        }

        function trackPointer(event) {
            const rect = this.getBoundingClientRect();
            const house = config.houses[state.house];
            const x = Math.round((event.clientX - rect.left) / rect.width * house.width);
            const y = Math.round((event.clientY - rect.top) / rect.height * house.height);
            $(this).find('.kbuilder-coords-x').text(pad(x, house.width));
            $(this).find('.kbuilder-coords-y').text(pad(y, house.height));
        }

        function designSummary() {
            const house = config.houses[state.house];
            const parts = [house.name];
            $.each(house.regions, function (regionKey, region) {
                const finish = selectedFinish(state.house, regionKey);
                parts.push(`${region.label}: ${finish ? finish.name : i18n.original}`);
            });
            return parts.join(' | ');
        }

        function openModal() {
            $modal.find(`input[name="${config.designField}"]`).val(designSummary());
            lastFocus = document.activeElement;
            $modal.prop('hidden', false);
            $('body').addClass('kbuilder-modal-open');
            $modal.find('input:visible, select:visible, textarea:visible').first().trigger('focus');
        }

        function closeModal() {
            if ($modal.prop('hidden')) {
                return;
            }
            $modal.prop('hidden', true);
            $('body').removeClass('kbuilder-modal-open');
            if (lastFocus) {
                lastFocus.focus();
            }
        }

        function downloadPdf(lead) {
            if (state.busy) {
                return Promise.resolve();
            }
            state.busy = true;
            const houseKey = state.house;
            const $button = $root.find('.kbuilder-download').prop('disabled', true).addClass('is-busy');
            const $label = $button.find('.kbuilder-download-label');
            const label = $label.text();
            $label.text(i18n.generating);

            const house = config.houses[houseKey];
            const regions = Object.keys(house.regions).map(function (regionKey) {
                return {
                    label: house.regions[regionKey].label,
                    color: selectedFinish(houseKey, regionKey),
                    shapes: houseEl(houseKey).find(`.kbuilder-region[data-region="${regionKey}"] path`).map(function () {
                        const transform = this.transform.baseVal.consolidate();
                        return {
                            d: this.getAttribute('d'),
                            rule: this.getAttribute('fill-rule') || 'nonzero',
                            matrix: transform ? transform.matrix : null,
                        };
                    }).get(),
                };
            });

            return buildPdf(config, house, regions, lead)
                .then(function (doc) {
                    doc.save(`${config.pdf.filename}-${houseKey}.pdf`);
                })
                .catch(function (error) {
                    console.error('[kbuilder]', error);
                    window.alert(i18n.pdfError);
                })
                .then(function () {
                    state.busy = false;
                    $button.prop('disabled', false).removeClass('is-busy');
                    $label.text(label);
                });
        }
    }

    function pad(value, max) {
        return String(Math.max(0, Math.min(value, max))).padStart(4, '0');
    }

    // Lead -------------------------------------------------------------------

    function readLead(form) {
        // Fluent Forms' Name field posts names[first_name], names[last_name], …
        const parts = $(form).find('input[name^="names["]').map(function () {
            return String(this.value).trim();
        }).get().filter(Boolean);
        const single = $(form).find('input[name="name"], input[name="full_name"]').val();

        return { name: parts.length ? parts.join(' ') : String(single || '').trim() };
    }

    function storeLead(lead) {
        try {
            window.sessionStorage.setItem(LEAD_STORAGE_KEY, JSON.stringify(lead));
        } catch (e) {
            // Storage blocked: the visitor will just see the form again next time.
        }
    }

    function storedLead() {
        try {
            return JSON.parse(window.sessionStorage.getItem(LEAD_STORAGE_KEY) || 'null');
        } catch (e) {
            return null;
        }
    }

    // PDF --------------------------------------------------------------------

    async function buildPdf(config, house, regions, lead) {
        const [photo, logo, textures] = await Promise.all([
            renderHouse(house, regions),
            loadLogo(config.pdf.logo).catch(function () {
                return null;
            }),
            Promise.all(regions.map(function (region) {
                return region.color && region.color.texture
                    ? loadSquare(region.color.texture).catch(function () {
                        return null;
                    })
                    : null;
            })),
        ]);

        const doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4', compress: true });
        doc.setProperties({ title: `K-Builder - ${house.name}`, creator: 'K-Builder' });
        writePdf(doc, { config, house, regions, photo, logo, textures, lead });

        return doc;
    }

    function writePdf(doc, data) {
        const { width: W, height: H, margin: M } = PAGE;
        const { config, house, regions, photo, logo, textures, lead } = data;
        const i18n = config.i18n;

        // Header
        if (logo) {
            const logoHeight = 18;
            doc.addImage(logo.data, 'PNG', M, M, logoHeight / logo.ratio, logoHeight);
        }
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(20);
        doc.setTextColor(RED);
        doc.text(i18n.pdfTitle, W - M, M + 7, { align: 'right' });
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.setTextColor(GREY);
        doc.text(`${house.name}  ·  ${formatDate(i18n.dateLocale)}`, W - M, M + 13, { align: 'right' });
        if (lead && lead.name) {
            doc.setTextColor(DARK);
            doc.text(`${i18n.preparedFor} ${lead.name}`, W - M, M + 18, { align: 'right' });
        }
        doc.setDrawColor(RED);
        doc.setLineWidth(0.6);
        doc.line(M, M + 23, W - M, M + 23);

        // House render
        const imageTop = M + 29;
        const imageWidth = 190;
        const imageHeight = imageWidth * house.height / house.width;
        doc.addImage(photo, 'JPEG', M, imageTop, imageWidth, imageHeight);

        // Selections, beside the render
        const colX = M + imageWidth + 9;
        const colWidth = W - M - colX;
        const chip = 15;
        const step = Math.min(23, (imageHeight - 10) / Math.max(regions.length, 1));
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        doc.setTextColor(DARK);
        doc.text(i18n.selections, colX, imageTop + 4);
        regions.forEach(function (region, index) {
            const y = imageTop + 10 + index * step;
            drawChip(doc, region.color, textures[index], colX, y, chip);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8.5);
            doc.setTextColor(GREY);
            doc.text(region.label.toUpperCase(), colX + chip + 4, y + 5);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(11);
            doc.setTextColor(DARK);
            doc.text(doc.splitTextToSize(region.color ? region.color.name : i18n.original, colWidth - chip - 4), colX + chip + 4, y + 10.5);
        });

        // Next steps
        const notesTop = imageTop + imageHeight + 12;
        if (config.pdf.contact) {
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(12);
            doc.setTextColor(DARK);
            doc.text(i18n.nextSteps, M, notesTop);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(10);
            const maxLines = Math.floor((H - M - 6 - (notesTop + 6)) / 4.5);
            doc.text(doc.splitTextToSize(config.pdf.contact, W - 2 * M).slice(0, maxLines), M, notesTop + 6);
        }

        // Footer
        doc.setDrawColor(LINE);
        doc.setLineWidth(0.3);
        doc.line(M, H - M - 2, W - M, H - M - 2);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(GREY);
        doc.text(i18n.disclaimer, M, H - M + 3);
        if (config.pdf.site) {
            doc.text(config.pdf.site, W - M, H - M + 3, { align: 'right' });
        }
    }

    function drawChip(doc, color, texture, x, y, size) {
        if (texture) {
            doc.addImage(texture, 'JPEG', x, y, size, size);
        } else if (color) {
            doc.setFillColor(color.hex);
            doc.rect(x, y, size, size, 'F');
        } else {
            // Nothing picked: the photo's original finish.
            doc.setDrawColor(LINE);
            doc.setLineWidth(0.3);
            doc.line(x, y + size, x + size, y);
        }
        doc.setDrawColor('#b4b4b4');
        doc.setLineWidth(0.3);
        doc.rect(x, y, size, size, 'S');
    }

    function formatDate(locale) {
        try {
            return new Date().toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' });
        } catch (e) {
            return new Date().toLocaleDateString();
        }
    }

    // Images -----------------------------------------------------------------

    /**
     * Paint the house on a canvas with the same fills and blend modes as the
     * on-screen SVG, so the PDF matches what the visitor designed. Each zone
     * is filled on its own layer first, then blended as a whole, like the
     * zone's <g> on screen.
     */
    async function renderHouse(house, regions) {
        const canvas = document.createElement('canvas');
        canvas.width = house.width;
        canvas.height = house.height;
        const ctx = canvas.getContext('2d');
        const layer = document.createElement('canvas');
        layer.width = house.width;
        layer.height = house.height;
        const layerCtx = layer.getContext('2d');

        drawCover(ctx, await loadImage(house.image), canvas.width, canvas.height);
        regions.forEach(function (region) {
            if (!region.color) {
                return;
            }
            layerCtx.clearRect(0, 0, layer.width, layer.height);
            layerCtx.fillStyle = region.color.hex;
            region.shapes.forEach(function (shape) {
                const path = new Path2D();
                path.addPath(new Path2D(shape.d), shape.matrix || undefined);
                layerCtx.fill(path, shape.rule);
            });
            ctx.save();
            ctx.globalAlpha = region.color.opacity;
            ctx.globalCompositeOperation = region.color.blend === 'normal' ? 'source-over' : region.color.blend;
            ctx.drawImage(layer, 0, 0);
            ctx.restore();
        });

        return canvas.toDataURL('image/jpeg', 0.9);
    }

    // Canvas equivalent of preserveAspectRatio="xMidYMid slice".
    function drawCover(ctx, image, width, height) {
        const scale = Math.max(width / image.naturalWidth, height / image.naturalHeight);
        const w = image.naturalWidth * scale;
        const h = image.naturalHeight * scale;
        ctx.drawImage(image, (width - w) / 2, (height - h) / 2, w, h);
    }

    async function loadSquare(src, size = 240) {
        const image = await loadImage(src);
        const side = Math.min(image.naturalWidth, image.naturalHeight);
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        canvas.getContext('2d').drawImage(image, (image.naturalWidth - side) / 2, (image.naturalHeight - side) / 2, side, side, 0, 0, size, size);

        return canvas.toDataURL('image/jpeg', 0.9);
    }

    async function loadLogo(url) {
        if (!url) {
            return null;
        }
        let src = url;
        if (/\.svg($|[?#])/i.test(url)) {
            // Give the SVG an explicit size; Firefox won't rasterise one that only has a viewBox.
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Could not load ${url}`);
            }
            const svg = new DOMParser().parseFromString(await response.text(), 'image/svg+xml').documentElement;
            const box = (svg.getAttribute('viewBox') || '').split(/[\s,]+/).map(Number);
            const ratio = box.length === 4 && box[2] > 0 ? box[3] / box[2] : 0.5;
            svg.setAttribute('width', LOGO_PX);
            svg.setAttribute('height', Math.round(LOGO_PX * ratio));
            src = URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(svg)], { type: 'image/svg+xml' }));
        }
        try {
            const image = await loadImage(src);
            const canvas = document.createElement('canvas');
            canvas.width = image.naturalWidth;
            canvas.height = image.naturalHeight;
            canvas.getContext('2d').drawImage(image, 0, 0);

            return { data: canvas.toDataURL('image/png'), ratio: canvas.height / canvas.width };
        } finally {
            if (src !== url) {
                URL.revokeObjectURL(src);
            }
        }
    }

    function loadImage(src) {
        return new Promise(function (resolve, reject) {
            const image = new Image();
            // Keeps the canvas exportable if assets are served from a CORS-enabled CDN.
            image.crossOrigin = 'anonymous';
            image.onload = function () {
                resolve(image);
            };
            image.onerror = function () {
                reject(new Error(`Could not load ${src}`));
            };
            image.src = src;
        });
    }
})(jQuery);
