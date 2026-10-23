<?php
/**
 * Builder markup, rendered by kbuilder_render().
 *
 * @var string $instance_id
 * @var array  $houses
 * @var array  $finishes
 * @var array  $region_finishes Finish IDs offered in each zone.
 * @var string $active_house
 * @var array  $config
 * @var string $form_html
 * @var bool   $show_notice
 * @var bool   $fullscreen
 */

defined( 'ABSPATH' ) || exit;

$original_chip = '<span class="kbuilder-chip kbuilder-chip--original"></span>';
?>
<div class="kbuilder<?php echo $fullscreen ? ' kbuilder--fullscreen' : ''; ?>" id="<?php echo esc_attr( $instance_id ); ?>" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
	<div class="kbuilder-app">
		<header class="kbuilder-topbar">
			<a class="kbuilder-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( kbuilder_image_url( 'kbuilder-logo-light.svg' ) ); ?>" alt="K-Builder · Karleton" width="158" height="44">
			</a>
			<p class="kbuilder-status"><span class="kbuilder-status-dot" aria-hidden="true"></span><?php esc_html_e( 'Configurador de fachadas', 'kbuilder' ); ?></p>
			<p class="kbuilder-readout">
				<span class="kbuilder-readout-label"><?php esc_html_e( 'Modelo', 'kbuilder' ); ?></span>
				<strong class="kbuilder-house-name"><?php echo esc_html( $houses[ $active_house ]['name'] ); ?></strong>
			</p>
			<?php if ( $fullscreen ) : ?>
				<a class="kbuilder-exit" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>
					<span><?php esc_html_e( 'Volver al sitio', 'kbuilder' ); ?></span>
				</a>
			<?php endif; ?>
		</header>

		<div class="kbuilder-main">
			<section class="kbuilder-viewport" aria-label="<?php esc_attr_e( 'Vista previa', 'kbuilder' ); ?>">
				<div class="kbuilder-stagebox">
					<?php foreach ( $houses as $house_key => $house ) : ?>
						<div class="kbuilder-house<?php echo $house_key === $active_house ? ' active' : ''; ?>" data-house="<?php echo esc_attr( $house_key ); ?>" style="--kb-ratio: <?php echo esc_attr( round( $house['width'] / $house['height'], 4 ) ); ?>;">
							<div class="kbuilder-stage">
								<svg class="kbuilder-canvas" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?php echo (int) $house['width']; ?> <?php echo (int) $house['height']; ?>" role="img" aria-label="<?php echo esc_attr( $house['name'] ); ?>">
									<image href="<?php echo esc_url( $house['image'] ); ?>" x="0" y="0" width="100%" height="100%" preserveAspectRatio="xMidYMid slice"/>
									<?php foreach ( $house['regions'] as $region_key => $region ) : ?>
										<g class="kbuilder-region" data-region="<?php echo esc_attr( $region_key ); ?>"><?php kbuilder_print_shapes( $region['shapes'] ); ?></g>
									<?php endforeach; ?>
									<?php foreach ( $house['regions'] as $region_key => $region ) : ?>
										<g class="kbuilder-outline" data-region="<?php echo esc_attr( $region_key ); ?>"><?php kbuilder_print_shapes( $region['shapes'] ); ?></g>
									<?php endforeach; ?>
								</svg>
								<div class="kbuilder-hotspots">
									<?php $number = 0; ?>
									<?php foreach ( $house['regions'] as $region_key => $region ) : ?>
										<?php
										$number++;
										$position = is_array( $region['hotspot'] )
											? sprintf( 'left: %s%%; top: %s%%;', (float) $region['hotspot'][0], (float) $region['hotspot'][1] )
											: '';
										?>
										<button type="button" class="kbuilder-hotspot" data-region="<?php echo esc_attr( $region_key ); ?>"<?php echo $position ? ' style="' . esc_attr( $position ) . '"' : ' data-auto'; ?> aria-label="<?php echo esc_attr( $region['label'] ); ?>">
											<span class="kbuilder-hotspot-reticle" aria-hidden="true"></span>
											<span class="kbuilder-hotspot-label" aria-hidden="true"><b><?php echo esc_html( sprintf( '%02d', $number ) ); ?></b><?php echo esc_html( $region['label'] ); ?></span>
										</button>
									<?php endforeach; ?>
								</div>
								<span class="kbuilder-corner kbuilder-corner--tl" aria-hidden="true"></span>
								<span class="kbuilder-corner kbuilder-corner--tr" aria-hidden="true"></span>
								<span class="kbuilder-corner kbuilder-corner--bl" aria-hidden="true"></span>
								<span class="kbuilder-corner kbuilder-corner--br" aria-hidden="true"></span>
								<span class="kbuilder-scan" aria-hidden="true"></span>
								<p class="kbuilder-coords" aria-hidden="true">X <span class="kbuilder-coords-x">----</span> Y <span class="kbuilder-coords-y">----</span></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( count( $houses ) > 1 ) : ?>
					<div class="kbuilder-models" role="group" aria-label="<?php esc_attr_e( 'Modelos', 'kbuilder' ); ?>">
						<?php $number = 0; ?>
						<?php foreach ( $houses as $house_key => $house ) : ?>
							<?php
							$number++;
							$is_active = $house_key === $active_house;
							?>
							<button type="button" class="kbuilder-model<?php echo $is_active ? ' active' : ''; ?>" data-house="<?php echo esc_attr( $house_key ); ?>" aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>">
								<img src="<?php echo esc_url( $house['thumb'] ); ?>" alt="" loading="lazy">
								<span><b><?php echo esc_html( sprintf( '%02d', $number ) ); ?></b><?php echo esc_html( $house['name'] ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>

			<aside class="kbuilder-panel" aria-label="<?php esc_attr_e( 'Acabados', 'kbuilder' ); ?>">
				<div class="kbuilder-panel-head">
					<h2 class="kbuilder-panel-title"><?php esc_html_e( 'Acabados', 'kbuilder' ); ?></h2>
					<p class="kbuilder-panel-hint"><?php esc_html_e( 'Elige una zona y aplica un acabado', 'kbuilder' ); ?></p>
					<?php if ( $show_notice ) : ?>
						<p class="kbuilder-notice"><?php esc_html_e( 'Only admins see this: no Fluent Forms form is set, so the PDF downloads without asking for contact details. Set it in K-Builder → Settings.', 'kbuilder' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="kbuilder-modules-wrap">
					<?php foreach ( $houses as $house_key => $house ) : ?>
						<div class="kbuilder-modules<?php echo $house_key === $active_house ? ' active' : ''; ?>" data-house="<?php echo esc_attr( $house_key ); ?>">
							<?php $number = 0; ?>
							<?php foreach ( $house['regions'] as $region_key => $region ) : ?>
								<?php
								$number++;
								$body_id = $instance_id . '-' . $house_key . '-' . $region_key;
								?>
								<div class="kbuilder-module" data-region="<?php echo esc_attr( $region_key ); ?>">
									<button type="button" class="kbuilder-module-head" aria-expanded="false" aria-controls="<?php echo esc_attr( $body_id ); ?>">
										<span class="kbuilder-module-index"><?php echo esc_html( sprintf( '%02d', $number ) ); ?></span>
										<svg class="kbuilder-module-icon" viewBox="0 0 68 63" aria-hidden="true"><path d="<?php echo esc_attr( $region['icon']['d'] ); ?>"<?php echo isset( $region['icon']['rule'] ) ? ' fill-rule="' . esc_attr( $region['icon']['rule'] ) . '"' : ''; ?>/></svg>
										<span class="kbuilder-module-label"><?php echo esc_html( $region['label'] ); ?></span>
										<span class="kbuilder-module-value"><?php echo $original_chip; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup. ?><span class="kbuilder-module-finish"><?php esc_html_e( 'Original', 'kbuilder' ); ?></span></span>
										<span class="kbuilder-module-caret" aria-hidden="true"></span>
									</button>
									<div class="kbuilder-module-body" id="<?php echo esc_attr( $body_id ); ?>" hidden>
										<div class="kbuilder-swatches" role="group" aria-label="<?php echo esc_attr( $region['label'] ); ?>">
											<button type="button" class="kbuilder-swatch selected" data-finish="" aria-pressed="true">
												<span class="kbuilder-swatch-frame"><?php echo $original_chip; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup. ?></span>
												<span class="kbuilder-swatch-name"><?php esc_html_e( 'Original', 'kbuilder' ); ?></span>
											</button>
											<?php foreach ( $region_finishes[ $region_key ] as $finish_id ) : ?>
												<button type="button" class="kbuilder-swatch" data-finish="<?php echo esc_attr( $finish_id ); ?>" aria-pressed="false">
													<span class="kbuilder-swatch-frame"><span class="kbuilder-chip" style="<?php echo esc_attr( kbuilder_chip_style( $finishes[ $finish_id ] ) ); ?>"></span></span>
													<span class="kbuilder-swatch-name"><?php echo esc_html( $finishes[ $finish_id ]['name'] ); ?></span>
												</button>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="kbuilder-actions">
					<button type="button" class="kbuilder-reset">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 1 0 2.4-5.7M4 4v4h4"/></svg>
						<span><?php esc_html_e( 'Restablecer', 'kbuilder' ); ?></span>
					</button>
					<button type="button" class="kbuilder-download">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0-5-5m5 5 5-5M4 19h16"/></svg>
						<span class="kbuilder-download-label"><?php esc_html_e( 'Descargar PDF', 'kbuilder' ); ?></span>
					</button>
				</div>
			</aside>
		</div>
	</div>

	<?php if ( '' !== trim( $form_html ) ) : ?>
		<div class="kbuilder-modal" hidden>
			<div class="kbuilder-modal-backdrop" data-kbuilder-close></div>
			<div class="kbuilder-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $instance_id ); ?>-modal-title">
				<button type="button" class="kbuilder-modal-close" data-kbuilder-close aria-label="<?php esc_attr_e( 'Cerrar', 'kbuilder' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" aria-hidden="true">
						<line x1="28" y1="12" x2="12" y2="28"/>
						<line x1="28" y1="28" x2="12" y2="12"/>
					</svg>
				</button>
				<p class="kbuilder-modal-kicker"><?php esc_html_e( 'Paso final', 'kbuilder' ); ?></p>
				<h2 class="kbuilder-modal-title" id="<?php echo esc_attr( $instance_id ); ?>-modal-title"><?php esc_html_e( 'Descarga tu diseño', 'kbuilder' ); ?></h2>
				<p class="kbuilder-modal-intro"><?php esc_html_e( 'Déjanos tus datos para descargar tu diseño en PDF. Un asesor de Karleton podrá contactarte para dar seguimiento a tu proyecto.', 'kbuilder' ); ?></p>
				<div class="kbuilder-modal-form">
					<?php echo $form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fluent Forms markup. ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
