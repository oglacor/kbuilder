# K-Builder

WordPress plugin for Karleton: visitors pick a house design, change the color of the roof, walls and windows, and download their design as a PDF after leaving their contact details in a Fluent Forms form.

## Install

1. Copy this folder to `wp-content/plugins/kbuilder` and activate **K-Builder**.
2. Add `[kbuilder]` to a page (`[kbuilder house="house-1"]` starts on a specific design).

## Lead form (Fluent Forms)

1. Create a form with **Name**, **Email** and **Phone** fields.
2. Form settings → Confirmation: **Same Page** (a redirect would cut off the download).
3. Optional: add a **Hidden** field with the name attribute `kbuilder_design`. The builder fills it with the chosen design (e.g. `Diseño 1 | Techo: Blanco | Paredes: Azul Cobalto | Ventanas: Nogal`), so it's saved with the entry and can go in the email notification as `{inputs.kbuilder_design}`.
4. Settings → K-Builder: pick the form and write the contact text printed in the PDF.

The PDF downloads when Fluent Forms reports a successful submission. After that, the visitor can download again during the same browser session without refilling the form. With no form selected (or Fluent Forms inactive) the PDF downloads straight away, and admins see a notice above the builder.

The PDF is generated in the browser (jsPDF, bundled in `assets/vendor`), so the server needs no PDF library. The gate is client-side: it guarantees normal visitors go through the form, but it isn't a security boundary.

## Adding a house design

1. Put the render in `assets/images/` (1920×1080 recommended).
2. Copy `houses/house-1.php` to `houses/house-2.php` and update the name, image and regions. Trace each paintable part over the render (e.g. in Illustrator, artboard the same size as the image) and paste the path data into `shapes`. Polygons work as `M x1 y1 x2 y2 … Z`.
3. Register it in `kbuilder_get_houses()` in `includes/catalog.php`.

Regions are free-form, so a design can add `doors` (there's a doors icon) or anything else. Each region names the palette it uses. The house switcher appears automatically once there's more than one design.

## Adding or tuning colors

Palettes live in `kbuilder_get_palettes()` in `includes/catalog.php`. Each color has a swatch (`hex`, or a `texture` image for wood finishes) and the tint settings applied to the photo: `blend` (any CSS `mix-blend-mode`) and `opacity`. The on-screen preview and the PDF both read these values.

Themes can also extend the catalog with the `kbuilder_palettes`, `kbuilder_houses` and `kbuilder_js_config` filters.

## Styles

Edit `assets/css/style.scss` and compile to `style.css`:

```
npx sass assets/css/style.scss assets/css/style.css
```
