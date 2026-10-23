<?php
/**
 * K-Builder → Guide: how to prepare and upload house models and finishes.
 */

defined( 'ABSPATH' ) || exit;

$samples  = KBUILDER_URL . 'assets/samples/';
$houses   = admin_url( 'edit.php?post_type=' . KBUILDER_HOUSE_TYPE );
$finishes = admin_url( 'edit.php?post_type=' . KBUILDER_FINISH_TYPE );
$settings = admin_url( 'edit.php?post_type=' . KBUILDER_HOUSE_TYPE . '&page=kbuilder' );
?>
<div class="wrap kbuilder-guide">
	<h1><?php esc_html_e( 'K-Builder guide', 'kbuilder' ); ?></h1>
	<p>
		K-Builder lets visitors recolor a house and download their design as a PDF. It's built from two lists you manage here:
		<a href="<?php echo esc_url( $houses ); ?>"><strong>House models</strong></a> (a photo of the house plus the outline of each paintable zone) and
		<a href="<?php echo esc_url( $finishes ); ?>"><strong>Finishes</strong></a> (the colors and textures, shared by every model).
		Nothing needs code: new models and finishes appear in the builder as soon as they're published.
	</p>

	<h2 id="standard">The standard: one canvas, 1920 × 1080 px</h2>
	<p>Every file for a house model is made on the <strong>same 1920 × 1080 px canvas</strong> (16:9). The photo fills it, and each zone outline is drawn on top of the photo at the same position, so they line up exactly.</p>
	<table class="widefat striped">
		<thead>
			<tr><th>File</th><th>Size</th><th>Format</th><th>How many</th></tr>
		</thead>
		<tbody>
			<tr>
				<td><strong>Background image</strong></td>
				<td>1920 × 1080 px exactly (16:9)</td>
				<td>JPG, sRGB, quality ~80, under 600 KB</td>
				<td>One per house model</td>
			</tr>
			<tr>
				<td><strong>Zone SVG</strong></td>
				<td>Canvas / artboard 1920 × 1080 px, starting at 0,0</td>
				<td>SVG (Illustrator: Export As, "Use Artboards" checked)</td>
				<td>One per zone: roof, walls, windows, doors (each optional)</td>
			</tr>
			<tr>
				<td><strong>Texture swatch</strong></td>
				<td>Square, at least 300 × 300 px</td>
				<td>JPG</td>
				<td>Optional, one per finish (wood grain, metal…)</td>
			</tr>
		</tbody>
	</table>
	<p>
		<a class="button" href="<?php echo esc_url( $samples . 'kbuilder-canvas-1920x1080.svg' ); ?>" download>Download the canvas template</a>
		&nbsp; Example of a complete model:
		<a href="<?php echo esc_url( $samples . 'house-1/background.jpg' ); ?>" download>background.jpg</a>,
		<a href="<?php echo esc_url( $samples . 'house-1/roof.svg' ); ?>" download>roof.svg</a>,
		<a href="<?php echo esc_url( $samples . 'house-1/walls.svg' ); ?>" download>walls.svg</a>,
		<a href="<?php echo esc_url( $samples . 'house-1/windows.svg' ); ?>" download>windows.svg</a>
	</p>

	<h2 id="image">1. The background image</h2>
	<ul>
		<li>Export the render or photo at <strong>1920 × 1080 px</strong>. Another size is shown stretched or cropped and the zones won't line up.</li>
		<li>Leave some space around the house; the builder shows the whole image, with markers over it.</li>
		<li><strong>Render the surfaces you want to recolor in white or a light, neutral grey.</strong> Most finishes darken the photo (Multiply), so a dark original wall can't become a light color.</li>
		<li>Keep real shadows and texture in the photo: the tint preserves them, which is what makes the result look real.</li>
	</ul>

	<h2 id="zones">2. The zone SVGs</h2>
	<p>A zone is a part of the house visitors can recolor. Each zone is a separate SVG file containing only the outline of that part, filled with any color. The color in the file doesn't matter; only the shape is used.</p>
	<ul>
		<li><strong>Roof</strong>: the visible roof surfaces or cap.</li>
		<li><strong>Walls</strong>: the facade, <strong>with the window and door openings cut out</strong>, so painting the walls doesn't cover the glass.</li>
		<li><strong>Windows</strong>: the frames only (the PVC profile). Cut out the glass, so the reflections stay untouched.</li>
		<li><strong>Doors</strong> (optional): the door leaf and frame.</li>
	</ul>

	<h3>In Illustrator</h3>
	<ol>
		<li><strong>File → New</strong>: width 1920 px, height 1080 px, RGB. Or open the canvas template above.</li>
		<li><strong>File → Place</strong> the background JPG. Set X 0, Y 0, W 1920, H 1080 (reference point top-left) and lock its layer.</li>
		<li>Make one layer per zone (Roof, Walls, Windows, Doors) and trace each part with the Pen tool. Give the shapes a fill, any color; strokes are ignored.</li>
		<li>For holes (the glass inside a frame, the openings in a wall): select the outer shape and the holes and use <strong>Object → Compound Path → Make</strong>, or <strong>Pathfinder → Minus Front</strong>.</li>
		<li>To export a zone: hide the photo and the other zone layers, then <strong>File → Export → Export As…</strong>, format <strong>SVG</strong>, <strong>check "Use Artboards"</strong>, Export. In SVG Options: Styling "Presentation Attributes", Decimal "2", Minify and Responsive unchecked.</li>
		<li>Repeat for each zone and name the files <code>roof.svg</code>, <code>walls.svg</code>, <code>windows.svg</code>, <code>doors.svg</code>.</li>
	</ol>
	<p class="kbuilder-guide-callout"><strong>"Use Artboards" is the step that matters most.</strong> Without it, Illustrator crops the SVG to the drawing and K-Builder can't tell where the shapes go. K-Builder rejects those files with a message instead of saving them misaligned.</p>

	<h3>In Figma</h3>
	<p>Use a 1920 × 1080 frame with the photo at 0,0. Put each zone in its own group, hide the photo and the other zones, and export the frame as SVG. The frame's background fill is ignored automatically.</p>

	<h3>If an upload fails</h3>
	<table class="widefat striped">
		<thead><tr><th>Message</th><th>Fix</th></tr></thead>
		<tbody>
			<tr><td>The SVG canvas is … × …, but it must be 1920 × 1080 px</td><td>Export with "Use Artboards" checked, from a 1920 × 1080 artboard.</td></tr>
			<tr><td>The SVG has no filled shapes</td><td>The shapes only have a stroke. Give them a fill (any color).</td></tr>
			<tr><td>The file is larger than 5 MB</td><td>Use Export As (not Save As, which embeds Illustrator data), or Object → Path → Simplify.</td></tr>
			<tr><td>Images, texts or symbols were ignored</td><td>Fine if they were guides. If they were part of the zone, expand them into shapes (Object → Expand).</td></tr>
		</tbody>
	</table>

	<h2 id="models">3. Adding a house model</h2>
	<ol>
		<li><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . KBUILDER_HOUSE_TYPE ) ); ?>">K-Builder → Add house model</a>. The title is the name visitors see (e.g. "Diseño 2").</li>
		<li><strong>Choose image</strong>: upload the 1920 × 1080 background.</li>
		<li>In <strong>Zones</strong>, choose an SVG for each zone the model has, then click <strong>Publish</strong> (or <strong>Save Draft</strong> to keep it hidden while you check it).</li>
		<li>Check the preview: each zone appears as a colored overlay and has to sit exactly over its part of the photo.</li>
		<li>Markers (the targets visitors click) are placed automatically. Drag one to move it, then click <strong>Update</strong>. "Reset marker" goes back to the automatic spot.</li>
		<li>The <strong>Order</strong> box (Page Attributes) sets the order of the models in the builder; the lowest number is shown first.</li>
	</ol>
	<p>To replace a zone, upload a new SVG for it; to remove it, check "Remove". Drafts and trashed models never appear on the site.</p>

	<h2 id="finishes">4. Finishes (colors and textures)</h2>
	<p>Finishes are shared by every house model. Name them like the product catalog (e.g. "Nogal", "Roble Dorado"), so the PDF a visitor downloads matches what they can order.</p>
	<table class="widefat striped">
		<tbody>
			<tr><th>Color</th><td>Tints the zone over the photo. For a texture finish, pick the texture's average color.</td></tr>
			<tr><th>Texture swatch</th><td>Optional square photo of the product (wood grain, anodized metal). Visitors pick it and it appears in the PDF.</td></tr>
			<tr><th>Blend mode</th><td><strong>Multiply</strong> for most colors (keeps shadows), <strong>Color</strong> for whites and greys on walls, <strong>Screen</strong> for white on dark surfaces, <strong>Normal</strong> + low opacity for flat light colors.</td></tr>
			<tr><th>Opacity</th><td>Strength of the tint. 80–100% for paint; 50–80% for frame finishes.</td></tr>
			<tr><th>Available for</th><td>The zones that offer it: paints for Walls and Roof, profile finishes for Windows and Doors.</td></tr>
		</tbody>
	</table>
	<p>The <strong>Preview</strong> on the finish screen shows the tint on a real house model as you change the settings. The Order box sets the swatch order.</p>

	<h2 id="page">5. Showing the builder</h2>
	<ul>
		<li><strong>Full screen</strong> (recommended): edit a page and choose the template <strong>K-Builder (full screen)</strong> (Page Attributes → Template). The builder fills the window, without the site header and footer.</li>
		<li><strong>Inside a page</strong>: add the shortcode <code>[kbuilder]</code>, or <code>[kbuilder house="slug"]</code> to start on a specific model.</li>
		<li>Links can open a model directly: <code>/kbuilder/?modelo=slug</code>. The slug of each model is in the House models list.</li>
	</ul>

	<h2 id="leads">6. Lead form and PDF</h2>
	<p>In <a href="<?php echo esc_url( $settings ); ?>">K-Builder → Settings</a>, pick the Fluent Forms form visitors fill in before the PDF downloads, and the contact text printed in the PDF.</p>
</div>
