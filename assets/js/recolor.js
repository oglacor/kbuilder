/**
 * Finish renderer, shared by the builder (screen, lens, PDF) and the admin preview.
 *
 * A zone is recolored rather than tinted: the photo's brightness inside the
 * zone, relative to its median, multiplies the finish's color or texture. The
 * photo's light and shadow survive while its original color doesn't, so a
 * white or light-wood finish works over dark frames. On frame zones (windows,
 * doors) the texture's grain is turned to run along each profile.
 */
(function () {
    'use strict';

    const MAX_TEXTURE = 1600; // px kept from HD textures (longest side)
    const BLUR = 2; // px; softens the photo's own grain in the shading map
    const HIGHLIGHT = 0.7; // share of the photo's highlights kept over the finish
    const textures = {};

    /**
     * @param {HTMLImageElement} photo Loaded house photo (covers the canvas, like the builder shows it).
     * @param {number} width  Canvas width (1920).
     * @param {number} height Canvas height (1080).
     */
    function Scene(photo, width, height) {
        const ctx = makeCanvas(width, height).getContext('2d');
        const scale = Math.max(width / photo.naturalWidth, height / photo.naturalHeight);
        const w = photo.naturalWidth * scale;
        const h = photo.naturalHeight * scale;
        ctx.drawImage(photo, (width - w) / 2, (height - h) / 2, w, h);

        this.width = width;
        this.height = height;
        this.photo = ctx.getImageData(0, 0, width, height);
        this.zones = {};
    }

    /**
     * @param {string}  key
     * @param {Array}   shapes  [{ d, rule, matrix }] as printed in the builder SVG.
     * @param {boolean} profile Frame zone: textures follow each profile's direction.
     */
    Scene.prototype.addZone = function (key, shapes, profile) {
        this.zones[key] = { shapes: shapes, profile: !!profile, data: null };
    };

    /**
     * The photo with every layer's finish applied.
     *
     * @param {Array} layers [{ zone, finish }] in paint order; finish: { hex, texture, scale, grain, shading }.
     * @return {Promise<ImageData>}
     */
    Scene.prototype.render = function (layers) {
        const scene = this;

        return withTextures(layers, function (layer, texture) {
            return texture ? textureAt(texture, layer.finish.scale) : null;
        }).then(function (prepared) {
            const out = new ImageData(new Uint8ClampedArray(scene.photo.data), scene.width, scene.height);
            prepared.forEach(function (item) {
                paintZone(scene, out, item.layer.finish, zoneData(scene, item.layer.zone), item.texture);
            });
            return out;
        });
    };

    /**
     * A close-up for the lens: a span × span area of the canvas around (cx, cy),
     * rendered at size × size with the texture sampled at that resolution.
     *
     * @return {Promise<ImageData>}
     */
    Scene.prototype.renderLens = function (layers, cx, cy, span, size) {
        const scene = this;
        const zoom = size / span;

        return withTextures(layers, function (layer, texture) {
            return texture ? textureAt(texture, layer.finish.scale * zoom) : null;
        }).then(function (prepared) {
            const out = new ImageData(size, size);
            const D = out.data;
            const zones = prepared.map(function (item) {
                return zoneData(scene, item.layer.zone);
            });
            const color = [0, 0, 0];

            for (let py = 0; py < size; py++) {
                for (let px = 0; px < size; px++) {
                    const x = cx + ((px + 0.5) / size - 0.5) * span;
                    const y = cy + ((py + 0.5) / size - 0.5) * span;
                    const o = (py * size + px) * 4;
                    samplePhoto(scene, x, y, color);
                    let r = color[0];
                    let g = color[1];
                    let b = color[2];

                    for (let n = 0; n < prepared.length; n++) {
                        const z = zones[n];
                        if (!z || z.empty) {
                            continue;
                        }
                        const lx = x - z.x - 0.5;
                        const ly = y - z.y - 0.5;
                        if (lx < -1 || ly < -1 || lx > z.w || ly > z.h) {
                            continue;
                        }
                        const a = bilinear(z.alpha, z.w, z.h, lx, ly, 0) / 255;
                        if (a <= 0) {
                            continue;
                        }
                        const finish = prepared[n].layer.finish;
                        const j = clampIndex(z, lx, ly);
                        finishColor(finish, prepared[n].texture, x, y, z.vertical ? z.vertical[j] : null, color);
                        shadeColor(color, bilinear(z.shade, z.w, z.h, lx, ly), finish.shading);
                        r += (color[0] - r) * a;
                        g += (color[1] - g) * a;
                        b += (color[2] - b) * a;
                    }

                    D[o] = r;
                    D[o + 1] = g;
                    D[o + 2] = b;
                    D[o + 3] = 255;
                }
            }
            return out;
        });
    };

    /**
     * The point inside a zone nearest to a hint (canvas px), for the lens to look at.
     * Without a hint, the zone's centre.
     *
     * @return {number[]|null} [x, y]
     */
    Scene.prototype.focus = function (key, hint) {
        const z = zoneData(this, key);
        if (!z || z.empty) {
            return null;
        }
        const hx = hint ? hint[0] - z.x : z.w / 2;
        const hy = hint ? hint[1] - z.y : z.h / 2;
        let best = null;
        let bestDistance = Infinity;
        for (let y = 0; y < z.h; y += 2) {
            for (let x = 0; x < z.w; x += 2) {
                if (z.alpha[y * z.w + x] < 200) {
                    continue;
                }
                const distance = (x - hx) * (x - hx) + (y - hy) * (y - hy);
                if (distance < bestDistance) {
                    bestDistance = distance;
                    best = [z.x + x + 0.5, z.y + y + 0.5];
                }
            }
        }
        return best;
    };

    // Zones -------------------------------------------------------------------

    /**
     * Mask, shading map and profile directions of a zone, inside its bounding box.
     * Built on first use and kept.
     */
    function zoneData(scene, key) {
        const zone = scene.zones[key];
        if (!zone) {
            return null;
        }
        if (zone.data) {
            return zone.data;
        }

        const W = scene.width;
        const H = scene.height;
        const ctx = makeCanvas(W, H).getContext('2d');
        ctx.fillStyle = '#fff';
        zone.shapes.forEach(function (shape) {
            const path = new Path2D();
            path.addPath(new Path2D(shape.d), shape.matrix || undefined);
            ctx.fill(path, shape.rule || 'nonzero');
        });
        const mask = ctx.getImageData(0, 0, W, H).data;

        let x0 = W;
        let y0 = H;
        let x1 = -1;
        let y1 = -1;
        for (let y = 0; y < H; y++) {
            for (let x = 0; x < W; x++) {
                if (mask[(y * W + x) * 4 + 3]) {
                    x0 = Math.min(x0, x);
                    x1 = Math.max(x1, x);
                    y0 = Math.min(y0, y);
                    y1 = y;
                }
            }
        }
        if (x1 < 0) {
            zone.data = { empty: true };
            return zone.data;
        }

        const w = x1 - x0 + 1;
        const h = y1 - y0 + 1;
        const alpha = new Uint8Array(w * h);
        const luminance = new Float32Array(w * h);
        const histogram = new Uint32Array(256);
        const P = scene.photo.data;
        let count = 0;
        for (let y = 0; y < h; y++) {
            for (let x = 0; x < w; x++) {
                const j = y * w + x;
                const i = ((y0 + y) * W + x0 + x) * 4;
                alpha[j] = mask[i + 3];
                luminance[j] = 0.2126 * P[i] + 0.7152 * P[i + 1] + 0.0722 * P[i + 2];
                if (alpha[j] > 127) {
                    histogram[luminance[j] | 0]++;
                    count++;
                }
            }
        }

        // The median brightness becomes 1: the finish shows its own color there.
        let median = 128;
        for (let v = 0, seen = 0; v < 256; v++) {
            seen += histogram[v];
            if (seen >= count / 2) {
                median = Math.max(v, 1);
                break;
            }
        }
        const shade = blurInside(luminance, alpha, w, h, BLUR);
        for (let j = 0; j < shade.length; j++) {
            shade[j] /= median;
        }

        zone.data = {
            x: x0,
            y: y0,
            w: w,
            h: h,
            alpha: alpha,
            shade: shade,
            vertical: zone.profile ? profileDirections(alpha, w, h) : null,
        };
        return zone.data;
    }

    // Box blur that only averages pixels inside the zone, so the shading
    // doesn't pick up the wall or glass around a thin frame.
    function blurInside(src, alpha, w, h, radius) {
        const pass = function (input, horizontal) {
            const output = new Float32Array(input.length);
            for (let y = 0; y < h; y++) {
                for (let x = 0; x < w; x++) {
                    const j = y * w + x;
                    let sum = 0;
                    let n = 0;
                    for (let k = -radius; k <= radius; k++) {
                        const xx = horizontal ? x + k : x;
                        const yy = horizontal ? y : y + k;
                        if (xx < 0 || yy < 0 || xx >= w || yy >= h) {
                            continue;
                        }
                        const m = yy * w + xx;
                        if (alpha[m] > 127) {
                            sum += input[m];
                            n++;
                        }
                    }
                    output[j] = n ? sum / n : input[j];
                }
            }
            return output;
        };

        return pass(pass(src, true), false);
    }

    // 1 where a pixel sits in a vertical profile (its vertical run is the longer one).
    function profileDirections(alpha, w, h) {
        const across = new Uint16Array(w * h);
        const down = new Uint16Array(w * h);
        const inside = function (j) {
            return alpha[j] > 127;
        };
        for (let y = 0; y < h; y++) {
            let x = 0;
            while (x < w) {
                if (!inside(y * w + x)) {
                    x++;
                    continue;
                }
                const start = x;
                while (x < w && inside(y * w + x)) {
                    x++;
                }
                for (let k = start; k < x; k++) {
                    across[y * w + k] = x - start;
                }
            }
        }
        for (let x = 0; x < w; x++) {
            let y = 0;
            while (y < h) {
                if (!inside(y * w + x)) {
                    y++;
                    continue;
                }
                const start = y;
                while (y < h && inside(y * w + x)) {
                    y++;
                }
                for (let k = start; k < y; k++) {
                    down[k * w + x] = y - start;
                }
            }
        }
        const vertical = new Uint8Array(w * h);
        for (let j = 0; j < vertical.length; j++) {
            vertical[j] = down[j] >= across[j] ? 1 : 0;
        }
        return vertical;
    }

    // Painting ------------------------------------------------------------------

    function paintZone(scene, out, finish, z, texture) {
        if (!z || z.empty) {
            return;
        }
        const D = out.data;
        const W = scene.width;
        const color = [0, 0, 0];

        for (let y = 0; y < z.h; y++) {
            for (let x = 0; x < z.w; x++) {
                const j = y * z.w + x;
                const a = z.alpha[j];
                if (!a) {
                    continue;
                }
                const cx = z.x + x;
                const cy = z.y + y;
                finishColor(finish, texture, cx + 0.5, cy + 0.5, z.vertical ? z.vertical[j] : null, color);
                shadeColor(color, z.shade[j], finish.shading);
                const i = (cy * W + cx) * 4;
                const k = a / 255;
                D[i] += (color[0] - D[i]) * k;
                D[i + 1] += (color[1] - D[i + 1]) * k;
                D[i + 2] += (color[2] - D[i + 2]) * k;
            }
        }
    }

    /**
     * The finish's own color at canvas point (x, y): its hex, or its texture
     * tiled at `scale` canvas px per texture width, with mirrored repeats to hide seams.
     *
     * @param {number|null} vertical 1/0 in a frame zone (profile direction), null elsewhere.
     */
    function finishColor(finish, texture, x, y, vertical, color) {
        if (!texture) {
            color[0] = finish.rgb[0];
            color[1] = finish.rgb[1];
            color[2] = finish.rgb[2];
            return;
        }
        const k = texture.width / finish.scale;
        // Turn the grain to run along the profile.
        const turn = vertical !== null && (vertical === 1) !== (finish.grain !== 'horizontal');
        const u = mirror(Math.floor((turn ? y : x) * k), texture.width);
        const v = mirror(Math.floor((turn ? x : y) * k), texture.height);
        const t = (v * texture.width + u) * 4;
        color[0] = texture.data[t];
        color[1] = texture.data[t + 1];
        color[2] = texture.data[t + 2];
    }

    // Finish × shading; brighter-than-median spots (reflections) lift towards white.
    function shadeColor(color, shade, strength) {
        const s = Math.pow(Math.max(shade, 0), strength);
        if (s <= 1) {
            color[0] *= s;
            color[1] *= s;
            color[2] *= s;
            return;
        }
        const lift = Math.min(s - 1, 1) * HIGHLIGHT;
        color[0] += (255 - color[0]) * lift;
        color[1] += (255 - color[1]) * lift;
        color[2] += (255 - color[2]) * lift;
    }

    function samplePhoto(scene, x, y, color) {
        const W = scene.width;
        const H = scene.height;
        const P = scene.photo.data;
        const fx = Math.min(Math.max(x - 0.5, 0), W - 1);
        const fy = Math.min(Math.max(y - 0.5, 0), H - 1);
        const x0 = Math.floor(fx);
        const y0 = Math.floor(fy);
        const x1 = Math.min(x0 + 1, W - 1);
        const y1 = Math.min(y0 + 1, H - 1);
        const tx = fx - x0;
        const ty = fy - y0;
        for (let c = 0; c < 3; c++) {
            const top = P[(y0 * W + x0) * 4 + c] * (1 - tx) + P[(y0 * W + x1) * 4 + c] * tx;
            const bottom = P[(y1 * W + x0) * 4 + c] * (1 - tx) + P[(y1 * W + x1) * 4 + c] * tx;
            color[c] = top * (1 - ty) + bottom * ty;
        }
    }

    /**
     * Bilinear sample of a zone array at array coordinates (x, y). Outside the
     * box: `outside` if given (0 for the mask), else the nearest edge value.
     */
    function bilinear(values, w, h, x, y, outside) {
        const x0 = Math.floor(x);
        const y0 = Math.floor(y);
        const tx = x - x0;
        const ty = y - y0;
        const at = function (xx, yy) {
            if (xx < 0 || yy < 0 || xx >= w || yy >= h) {
                if (outside !== undefined) {
                    return outside;
                }
                xx = Math.min(Math.max(xx, 0), w - 1);
                yy = Math.min(Math.max(yy, 0), h - 1);
            }
            return values[yy * w + xx];
        };
        return (at(x0, y0) * (1 - tx) + at(x0 + 1, y0) * tx) * (1 - ty) + (at(x0, y0 + 1) * (1 - tx) + at(x0 + 1, y0 + 1) * tx) * ty;
    }

    function clampIndex(z, x, y) {
        const xx = Math.min(Math.max(Math.round(x), 0), z.w - 1);
        const yy = Math.min(Math.max(Math.round(y), 0), z.h - 1);
        return yy * z.w + xx;
    }

    function mirror(n, size) {
        const period = size * 2;
        let m = n % period;
        if (m < 0) {
            m += period;
        }
        return m < size ? m : period - 1 - m;
    }

    // Textures ------------------------------------------------------------------

    // Loads each layer's texture (if any) and asks `pick` for the resolution it needs.
    function withTextures(layers, pick) {
        return Promise.all(layers.map(function (layer) {
            layer.finish.rgb = layer.finish.rgb || hexToRgb(layer.finish.hex);
            if (!layer.finish.texture) {
                return { layer: layer, texture: null };
            }
            return loadTexture(layer.finish.texture)
                .then(function (texture) {
                    return { layer: layer, texture: pick(layer, texture) };
                })
                .catch(function (error) {
                    // A texture that can't be read falls back to the finish's color.
                    console.error('[kbuilder]', error);
                    return { layer: layer, texture: null };
                });
        }));
    }

    /**
     * An HD texture, with its uneven lighting flattened: each pixel is divided
     * by a heavily blurred copy, so tiles don't show light and dark blotches.
     */
    function loadTexture(url) {
        if (!textures[url]) {
            textures[url] = loadImage(url).then(function (image) {
                const fit = Math.min(1, MAX_TEXTURE / Math.max(image.naturalWidth, image.naturalHeight));
                const w = Math.max(1, Math.round(image.naturalWidth * fit));
                const h = Math.max(1, Math.round(image.naturalHeight * fit));
                const source = makeCanvas(w, h);
                const ctx = source.getContext('2d');
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(image, 0, 0, w, h);

                const lighting = makeCanvas(w, h);
                const lctx = lighting.getContext('2d');
                lctx.imageSmoothingQuality = 'high';
                lctx.drawImage(shrink(source, 8, 8), 0, 0, w, h);

                const pixels = ctx.getImageData(0, 0, w, h);
                const light = lctx.getImageData(0, 0, w, h).data;
                const D = pixels.data;
                const mean = [0, 0, 0];
                for (let i = 0; i < light.length; i += 4) {
                    mean[0] += light[i];
                    mean[1] += light[i + 1];
                    mean[2] += light[i + 2];
                }
                const n = light.length / 4;
                for (let i = 0; i < D.length; i += 4) {
                    for (let c = 0; c < 3; c++) {
                        D[i + c] = D[i + c] * (mean[c] / n) / Math.max(light[i + c], 1);
                    }
                }
                ctx.putImageData(pixels, 0, 0);

                return { canvas: source, sizes: {} };
            });
        }
        return textures[url];
    }

    // The texture resampled to `width` px wide (one texel per output pixel), cached.
    function textureAt(texture, width) {
        const w = Math.max(8, Math.min(texture.canvas.width, Math.round(width / 4) * 4));
        if (!texture.sizes[w]) {
            const h = Math.max(8, Math.round(texture.canvas.height * w / texture.canvas.width));
            const small = shrink(texture.canvas, w, h);
            texture.sizes[w] = small.getContext('2d').getImageData(0, 0, w, h);
        }
        return texture.sizes[w];
    }

    // Downscale in halves, so fine grain averages out instead of aliasing.
    function shrink(canvas, width, height) {
        let current = canvas;
        while (current.width / 2 > width && current.height / 2 > height) {
            const half = makeCanvas(Math.round(current.width / 2), Math.round(current.height / 2));
            const ctx = half.getContext('2d');
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(current, 0, 0, half.width, half.height);
            current = half;
        }
        const out = makeCanvas(width, height);
        const ctx = out.getContext('2d');
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(current, 0, 0, width, height);
        return out;
    }

    // Helpers -------------------------------------------------------------------

    function makeCanvas(width, height) {
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        return canvas;
    }

    function hexToRgb(hex) {
        const value = /^#?([0-9a-f]{6})$/i.exec(String(hex || ''));
        const n = value ? parseInt(value[1], 16) : 0xffffff;
        return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
    }

    function loadImage(src) {
        return new Promise(function (resolve, reject) {
            const image = new Image();
            // Keeps the canvas readable if media is served from a CORS-enabled CDN.
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

    /**
     * A lens image as a circular PNG data URL (transparent corners), for the PDF.
     */
    function circleImage(imageData) {
        const square = makeCanvas(imageData.width, imageData.height);
        square.getContext('2d').putImageData(imageData, 0, 0);
        const round = makeCanvas(imageData.width, imageData.height);
        const ctx = round.getContext('2d');
        ctx.beginPath();
        ctx.arc(round.width / 2, round.height / 2, round.width / 2, 0, Math.PI * 2);
        ctx.clip();
        ctx.drawImage(square, 0, 0);
        return round.toDataURL('image/png');
    }

    window.KBuilderRecolor = {
        Scene: Scene,
        loadImage: loadImage,
        circleImage: circleImage,
    };
})();
