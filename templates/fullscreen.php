<?php
/**
 * Full-screen page template (see includes/page-template.php).
 */

defined( 'ABSPATH' ) || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#06080b">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'kbuilder-page' ); ?>>
<?php wp_body_open(); ?>
<main class="kbuilder-page-main">
	<h1 class="kbuilder-sr-only"><?php single_post_title(); ?></h1>
	<?php echo kbuilder_render( array( 'fullscreen' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in templates/builder.php. ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
