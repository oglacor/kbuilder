/**
 * Places a zone's marker when none was set by hand in the admin: the point
 * inside the zone's shapes with the most room around it, nearest their centre.
 * Shared by the builder and the admin preview so both show the same spot.
 */
(function () {
    'use strict';

    const GRID = 32;

    /**
     * @param {SVGGElement} group The zone's <g>, rendered (not inside display: none).
     * @return {number[]|null} [x%, y%] of the canvas, or null when it can't be measured.
     */
    window.kbuilderAutoHotspot = function (group) {
        const svg = group.ownerSVGElement;
        const paths = Array.prototype.slice.call(group.querySelectorAll('path'));
        let box;
        try {
            box = group.getBBox();
        } catch (e) {
            return null;
        }
        if (!svg || !paths.length || !box.width || !box.height) {
            return null;
        }

        const view = svg.viewBox.baseVal;
        const toPercent = function (x, y) {
            return [round((x - view.x) / view.width * 100), round((y - view.y) / view.height * 100)];
        };
        const inverses = paths.map(function (path) {
            const transform = path.transform.baseVal.consolidate();
            return transform ? transform.matrix.inverse() : null;
        });
        const inside = function (x, y) {
            return paths.some(function (path, i) {
                const point = inverses[i] ? new DOMPoint(x, y).matrixTransform(inverses[i]) : new DOMPoint(x, y);
                return path.isPointInFill(point);
            });
        };

        const cx = box.x + box.width / 2;
        const cy = box.y + box.height / 2;
        const stepX = box.width / GRID;
        const stepY = box.height / GRID;
        const hits = [];
        for (let row = 0; row < GRID; row++) {
            hits[row] = [];
            for (let col = 0; col < GRID; col++) {
                hits[row][col] = inside(box.x + (col + 0.5) * stepX, box.y + (row + 0.5) * stepY);
            }
        }

        let best = null;
        let bestScore = -Infinity;
        for (let row = 0; row < GRID; row++) {
            for (let col = 0; col < GRID; col++) {
                if (!hits[row][col]) {
                    continue;
                }
                const x = box.x + (col + 0.5) * stepX;
                const y = box.y + (row + 0.5) * stepY;
                // Room first (filled neighbours), then distance to the centre.
                const room = [[-1, 0], [1, 0], [0, -1], [0, 1], [-1, -1], [1, 1], [-1, 1], [1, -1]].filter(function (d) {
                    return hits[row + d[1]] && hits[row + d[1]][col + d[0]];
                }).length;
                const score = room * 1e6 - Math.hypot(x - cx, y - cy);
                if (score > bestScore) {
                    bestScore = score;
                    best = [x, y];
                }
            }
        }
        if (best) {
            return toPercent(best[0], best[1]);
        }

        // Shapes thinner than the grid: a point on the longest outline.
        let longest = null;
        let length = 0;
        paths.forEach(function (path, i) {
            const total = path.getTotalLength();
            if (total > length) {
                length = total;
                longest = i;
            }
        });
        if (null === longest) {
            return toPercent(cx, cy);
        }
        const transform = paths[longest].transform.baseVal.consolidate();
        let point = paths[longest].getPointAtLength(length / 2);
        if (transform) {
            point = new DOMPoint(point.x, point.y).matrixTransform(transform.matrix);
        }

        return toPercent(point.x, point.y);
    };

    function round(value) {
        return Math.round(value * 100) / 100;
    }
})();
