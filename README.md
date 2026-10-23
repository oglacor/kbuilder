# K-Builder

WordPress plugin for Karleton: visitors pick a house model, change the finishes of the roof, walls, windows and doors, and download their design as a PDF after leaving their contact details in a Fluent Forms form.

House models and finishes are managed in the admin (**K-Builder** menu), so new designs and products need no code. **K-Builder → Guide** in the admin has the same instructions as this file, written for whoever prepares the files.

## Install

1. Copy this folder to `wp-content/plugins/kbuilder` and activate **K-Builder**.
2. Show the builder:
   - **Full screen** (recommended): edit a page and choose the template **K-Builder (full screen)**. The builder fills the window, without the theme's header, footer or sidebar.
   - **Inside a page**: add `[kbuilder]`, or `[kbuilder house="slug"]` to start on a specific model.
   - Links can open a model directly with `?modelo=slug`.

On first load, version 2.0 creates the 1.x catalog in the admin: the "Diseño 1" model (slug `house-1`, built from `assets/samples/house-1/`) and the 21 finishes. Sites updating from 1.x keep the same builder.

## The standard: one 1920 × 1080 canvas

| File | Size | Format | How many |
| --- | --- | --- | --- |
| Background image | 1920 × 1080 px exactly (16:9) | JPG, sRGB, quality ~80, under 600 KB | One per house model |
| Zone SVG | Canvas / artboard 1920 × 1080 px, starting at 0,0 | SVG, Illustrator Export As with **Use Artboards** | One per zone: roof, walls, windows, doors (each optional) |
| Texture | 1024–2048 px, square or close to it | JPG, straight-on, evenly lit, seamless if possible | Optional, one per finish |

The photo fills the canvas and each zone outline is drawn over it at the same position. `assets/samples/kbuilder-canvas-1920x1080.svg` is a blank canvas, and `assets/samples/house-1/` is a complete model to copy from.

### Background image

- Exactly 1920 × 1080 px; the admin warns about other proportions.
- Render the surfaces to recolor in white or a light, neutral grey: most finishes multiply, so a dark original can't become a light color.

### Zone SVGs

One file per zone, containing only that zone's shapes with a fill (any color; only the geometry is used):

- **Roof**: the visible roof surfaces.
- **Walls**: the facade, with window and door openings cut out (Pathfinder → Minus Front).
- **Windows**: the frames only; cut the glass out with a compound path (Object → Compound Path → Make).
- **Doors** (optional): leaf and frame.

Illustrator: 1920 × 1080 px artboard, place the photo at 0,0 and lock it, trace each zone on its own layer, then for each zone hide the other layers and **File → Export → Export As → SVG** with **Use Artboards** checked (Styling: Presentation Attributes, Decimal: 2).

## Adding a house model

**K-Builder → Add house model**: title (shown to visitors), **Choose image**, one SVG per zone in **Zones**, then Publish. The preview overlays each zone in color to check the alignment; markers are placed automatically and can be dragged. **Order** (Page Attributes) sorts the models. Only published models with an image and at least one zone appear on the site.

How uploads are handled (`includes/svg.php`): the SVG file is never stored or printed. It's parsed with DOMDocument (DTD stripped, no network), and only the path data of filled shapes is kept: `path`, `rect`, `circle`, `ellipse`, `polygon`, `polyline`. Group transforms and the viewBox are folded into one matrix per shape. The parser rejects files whose canvas isn't 16:9 (the usual missing "Use Artboards"). It skips hidden layers, unfilled shapes, a full-canvas background rect, and images, text and `<use>`, and tells the uploader what it ignored.

## Finishes

**K-Builder → Finishes**, shared by every model. Each finish has:

- **Color**: the finish color, and the swatch when there's no texture.
- **Texture**: optional HD photo of the product, painted on the zone (and shown in the swatch, the lens and the PDF).
- **Grain size** (canvas px one texture copy spans), **grain direction** (in the file) and **shading** (% of the photo's light and shadow kept).
- **Available for**: the zones that offer it.

The finish screen previews the finish live on the first house model, lens included.

How finishes are painted (`assets/js/recolor.js`): each zone is recolored in a canvas, not tinted with a blend mode. The photo's brightness inside the zone, relative to the zone's median and lightly blurred, multiplies the finish color or texture; highlights above the median lift towards white. The photo keeps its light and shadow but loses its original color, so light finishes work over dark frames. Textures are flattened (divided by a heavily blurred copy, which removes uneven lighting), resampled to the size they're drawn at, and tiled with mirrored repeats. On frame zones (`profile` in `kbuilder_region_types`: windows, doors) each pixel's profile direction is taken from the mask's horizontal vs vertical run length, and the grain is turned to follow it. The screen, the lens (the same render, sampled at a higher resolution around the zone's marker) and the PDF share these pixels. Media must be same-origin, or served with CORS headers, so the canvas can read them.

When a zone is open, the builder shows the **lens**: a round close-up of the zone with its finish and name, placed in the nearest stage corner that doesn't cover the spot. The PDF prints the same close-up for every zone.

## Lead form (Fluent Forms)

1. Create a form with **Name**, **Email** and **Phone** fields.
2. Form settings → Confirmation: **Same Page** (a redirect would cut off the download).
3. Optional: add a **Hidden** field with the name attribute `kbuilder_design`. The builder fills it with the chosen design (e.g. `Diseño 1 | Techo: Blanco | Paredes: Azul Cobalto | Ventanas: Nogal`), so it's saved with the entry and can go in the email notification as `{inputs.kbuilder_design}`.
4. **K-Builder → Settings**: pick the form and write the contact text printed in the PDF.

The PDF downloads when Fluent Forms reports a successful submission. After that, the visitor can download again during the same browser session without refilling the form. With no form selected (or Fluent Forms inactive) the PDF downloads straight away, and admins see a notice in the builder.

The PDF is generated in the browser (jsPDF, bundled in `assets/vendor`), so the server needs no PDF library. The gate is client-side: it guarantees normal visitors go through the form, but it isn't a security boundary.

## Code map

- `includes/post-types.php`: the `kbuilder_house` and `kbuilder_finish` post types (private; editors and up can manage them).
- `includes/catalog.php`: zone types and the readers that turn posts into the arrays the builder uses.
- `includes/svg.php`: zone SVG parser.
- `includes/install.php`: one-time creation of the 1.x catalog.
- `includes/shortcode.php`, `includes/page-template.php`, `templates/builder.php`, `templates/fullscreen.php`: front end.
- `includes/admin*.php`, `templates/admin-guide.php`, `assets/js/admin.js`: admin screens.
- `assets/js/hotspot.js`: automatic marker placement, shared by the builder and the admin preview.
- `assets/js/recolor.js`: the finish renderer (screen, lens, PDF, admin preview).

Filters: `kbuilder_region_types` (add a zone type, e.g. a gate), `kbuilder_finishes`, `kbuilder_houses`, `kbuilder_js_config`.

## Styles

Edit `assets/css/style.scss` and compile to `style.css`:

```
npx sass --no-source-map assets/css/style.scss assets/css/style.css
```
