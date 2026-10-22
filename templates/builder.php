<?php
/**
 * Builder markup, rendered by kbuilder_render_shortcode().
 *
 * @var string $instance_id
 * @var array  $houses
 * @var array  $palettes
 * @var string $active_house
 * @var array  $config
 * @var string $form_html
 * @var bool   $show_notice
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( $show_notice ) : ?>
	<p class="kbuilder-notice">
		<?php esc_html_e( 'K-Builder (only admins see this): no Fluent Forms form is configured, so the PDF downloads without asking for contact details. Set it in Settings → K-Builder.', 'kbuilder' ); ?>
	</p>
<?php endif; ?>
<div class="kbuilder" id="<?php echo esc_attr( $instance_id ); ?>" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
	<div class="kbuilder-logo">
		<img src="<?php echo esc_url( kbuilder_image_url( 'kbuilder-logo.svg' ) ); ?>" alt="K-Builder">
	</div>

	<?php foreach ( $houses as $house_key => $house ) : ?>
		<div class="kbuilder-house<?php echo $house_key === $active_house ? ' active' : ''; ?>" data-house="<?php echo esc_attr( $house_key ); ?>">
			<div class="kbuilder-stage" style="aspect-ratio: <?php echo (int) $house['width']; ?> / <?php echo (int) $house['height']; ?>;">
				<svg class="kbuilder-canvas" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?php echo (int) $house['width']; ?> <?php echo (int) $house['height']; ?>" role="img" aria-label="<?php echo esc_attr( $house['name'] ); ?>">
					<image href="<?php echo esc_url( $house['image'] ); ?>" x="0" y="0" width="100%" height="100%" preserveAspectRatio="xMidYMid slice"/>
					<?php foreach ( $house['regions'] as $region_key => $region ) : ?>
						<g class="kbuilder-region" data-region="<?php echo esc_attr( $region_key ); ?>">
							<?php foreach ( $region['shapes'] as $shape ) : ?>
								<path d="<?php echo esc_attr( $shape ); ?>"/>
							<?php endforeach; ?>
						</g>
					<?php endforeach; ?>
				</svg>
				<div class="kbuilder-hotspots">
					<?php foreach ( $house['regions'] as $region_key => $region ) : ?>
						<button type="button" class="kbuilder-hotspot" data-region="<?php echo esc_attr( $region_key ); ?>" style="left: <?php echo (float) $region['hotspot'][0]; ?>%; top: <?php echo (float) $region['hotspot'][1]; ?>%;" aria-label="<?php echo esc_attr( $region['label'] ); ?>">
							<img src="<?php echo esc_url( kbuilder_image_url( 'action-button.svg' ) ); ?>" alt="">
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="kbuilder-panel">
				<div class="kbuilder-panel-nav">
					<?php foreach ( $house['regions'] as $region_key => $region ) : ?>
						<?php $icon = kbuilder_get_region_icon( $region['icon'] ); ?>
						<button type="button" class="kbuilder-nav-button" data-region="<?php echo esc_attr( $region_key ); ?>" aria-label="<?php echo esc_attr( $region['label'] ); ?>" title="<?php echo esc_attr( $region['label'] ); ?>" aria-pressed="false">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 68 63" aria-hidden="true">
								<rect class="bg" x="0" y="0" width="68" height="63"/>
								<path class="icon" d="<?php echo esc_attr( $icon['d'] ); ?>"<?php echo isset( $icon['rule'] ) ? ' fill-rule="' . esc_attr( $icon['rule'] ) . '"' : ''; ?>/>
							</svg>
						</button>
					<?php endforeach; ?>
				</div>
				<button type="button" class="kbuilder-panel-close" aria-label="<?php esc_attr_e( 'Cerrar', 'kbuilder' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" aria-hidden="true">
						<line x1="30" y1="10" x2="10" y2="30"/>
						<line x1="30" y1="30" x2="10" y2="10"/>
					</svg>
				</button>
				<?php foreach ( $house['regions'] as $region_key => $region ) : ?>
					<div class="kbuilder-selector" data-region="<?php echo esc_attr( $region_key ); ?>">
						<h2 class="kbuilder-selector-title"><?php echo esc_html( $region['label'] ); ?></h2>
						<div class="kbuilder-swatches">
							<?php foreach ( $palettes[ $region['palette'] ] as $color_key => $color ) : ?>
								<?php
								$chip_style = empty( $color['texture'] )
									? 'background-color: ' . $color['hex'] . ';'
									: 'background-image: url("' . esc_url( $color['texture'] ) . '");';
								?>
								<button type="button" class="kbuilder-swatch" data-color="<?php echo esc_attr( $color_key ); ?>" aria-pressed="false">
									<span class="kbuilder-swatch-chip" style="<?php echo esc_attr( $chip_style ); ?>"></span>
									<span class="kbuilder-swatch-name"><?php echo esc_html( $color['name'] ); ?></span>
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>

	<div class="kbuilder-toolbar">
		<?php if ( count( $houses ) > 1 ) : ?>
			<div class="kbuilder-house-switch">
				<?php foreach ( $houses as $house_key => $house ) : ?>
					<?php $is_active = $house_key === $active_house; ?>
					<button type="button" class="kbuilder-house-button<?php echo $is_active ? ' active' : ''; ?>" data-house="<?php echo esc_attr( $house_key ); ?>" aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>">
						<?php echo esc_html( $house['name'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<button type="button" class="kbuilder-download">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0-5-5m5 5 5-5M4 19h16"/></svg>
			<span class="kbuilder-download-label"><?php esc_html_e( 'Descargar PDF', 'kbuilder' ); ?></span>
		</button>
	</div>

	<?php if ( '' !== trim( $form_html ) ) : ?>
		<div class="kbuilder-modal" hidden>
			<div class="kbuilder-modal-backdrop" data-kbuilder-close></div>
			<div class="kbuilder-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $instance_id ); ?>-modal-title">
				<button type="button" class="kbuilder-modal-close" data-kbuilder-close aria-label="<?php esc_attr_e( 'Cerrar', 'kbuilder' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" aria-hidden="true">
						<line x1="30" y1="10" x2="10" y2="30"/>
						<line x1="30" y1="30" x2="10" y2="10"/>
					</svg>
				</button>
				<h2 class="kbuilder-modal-title" id="<?php echo esc_attr( $instance_id ); ?>-modal-title"><?php esc_html_e( 'Descarga tu diseño', 'kbuilder' ); ?></h2>
				<p class="kbuilder-modal-intro"><?php esc_html_e( 'Déjanos tus datos para descargar tu diseño en PDF. Un asesor de Karleton podrá contactarte para dar seguimiento a tu proyecto.', 'kbuilder' ); ?></p>
				<div class="kbuilder-modal-form">
					<?php echo $form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fluent Forms markup. ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
