<?php
/**
 * Reads the paintable shapes out of an uploaded zone SVG.
 *
 * Only the geometry is kept, never the file: each filled shape becomes path
 * data plus an optional matrix and fill rule, so the markup the builder prints
 * is always our own. Group transforms and the file's viewBox are folded into
 * that matrix, which maps the shape onto the standard 1920 × 1080 canvas.
 */

defined( 'ABSPATH' ) || exit;

define( 'KBUILDER_SVG_MAX_BYTES', 5 * MB_IN_BYTES );

/**
 * @param string $svg File contents.
 * @return array|WP_Error {
 *     shapes: list of { d: path data, m?: [a, b, c, d, e, f], r?: 'evenodd' },
 *     notes:  things the uploader should know (skipped elements, rescaling).
 * }
 */
function kbuilder_parse_svg( $svg ) {
	if ( strlen( $svg ) > KBUILDER_SVG_MAX_BYTES ) {
		return new WP_Error( 'kbuilder_svg_size', __( 'The file is larger than 5 MB. Export with File → Export → Export As… (not Save As), or simplify the paths.', 'kbuilder' ) );
	}

	// Drop the DTD (no entity expansion or external references), then fill the
	// entities Illustrator's "Save As" puts in its namespace declarations.
	$svg = preg_replace( '/<!DOCTYPE[^\[>]*(\[[^\]]*\])?\s*>/i', '', $svg );
	$svg = preg_replace( '/&(?!(?:amp|lt|gt|quot|apos|#\d+|#x[0-9a-f]+);)[\w.-]+;/i', 'urn:kbuilder:entity', $svg );

	$dom      = new DOMDocument();
	$previous = libxml_use_internal_errors( true );
	$loaded   = $dom->loadXML( $svg, LIBXML_NONET | LIBXML_COMPACT );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $loaded || ! $dom->documentElement || 'svg' !== $dom->documentElement->localName ) {
		return new WP_Error( 'kbuilder_svg_invalid', __( 'This isn\'t a valid SVG file.', 'kbuilder' ) );
	}

	$root = $dom->documentElement;
	$box  = kbuilder_svg_viewbox( $root );
	if ( ! $box ) {
		return new WP_Error( 'kbuilder_svg_size', __( 'The SVG has no canvas size (viewBox). Export it from a 1920 × 1080 px artboard with "Use Artboards" checked.', 'kbuilder' ) );
	}

	list( $x, $y, $width, $height ) = $box;
	$expected = KBUILDER_CANVAS_WIDTH / KBUILDER_CANVAS_HEIGHT;
	if ( abs( ( $width / $height ) / $expected - 1 ) > 0.01 ) {
		return new WP_Error(
			'kbuilder_svg_canvas',
			sprintf(
				/* translators: 1: width, 2: height */
				__( 'The SVG canvas is %1$s × %2$s, but it must be 1920 × 1080 px, the same as the background image. In Illustrator use a 1920 × 1080 px artboard and check "Use Artboards" when exporting.', 'kbuilder' ),
				kbuilder_svg_number( $width ),
				kbuilder_svg_number( $height )
			)
		);
	}

	$context = array(
		'classes'    => kbuilder_svg_class_styles( $dom ),
		'canvas'     => array( $x, $y, $width, $height ),
		'shapes'     => array(),
		'unfilled'   => 0,
		'background' => 0,
		'ignored'    => 0,
	);
	$sx    = KBUILDER_CANVAS_WIDTH / $width;
	$sy    = KBUILDER_CANVAS_HEIGHT / $height;
	$map   = array( $sx, 0, 0, $sy, -$x * $sx, -$y * $sy );
	$style = kbuilder_svg_style( $root, $context['classes'] ); // Figma sets fill="none" on the root.

	kbuilder_svg_walk(
		$root,
		$map,
		array(
			'fill'      => isset( $style['fill'] ) ? $style['fill'] : '',
			'fill-rule' => isset( $style['fill-rule'] ) ? $style['fill-rule'] : 'nonzero',
		),
		$context
	);

	if ( ! $context['shapes'] ) {
		return new WP_Error( 'kbuilder_svg_empty', __( 'The SVG has no filled shapes. Give the zone\'s shapes a fill (any color) and export again.', 'kbuilder' ) );
	}

	$notes = array();
	if ( abs( $width - KBUILDER_CANVAS_WIDTH ) > 0.5 ) {
		/* translators: 1: width, 2: height */
		$notes[] = sprintf( __( 'The canvas is %1$s × %2$s; the shapes were scaled to 1920 × 1080.', 'kbuilder' ), kbuilder_svg_number( $width ), kbuilder_svg_number( $height ) );
	}
	if ( $context['background'] ) {
		$notes[] = __( 'A shape covering the whole canvas was ignored (it looks like a background).', 'kbuilder' );
	}
	if ( $context['unfilled'] ) {
		/* translators: %d: number of shapes */
		$notes[] = sprintf( _n( '%d shape without a fill was ignored.', '%d shapes without a fill were ignored.', $context['unfilled'], 'kbuilder' ), $context['unfilled'] );
	}
	if ( $context['ignored'] ) {
		/* translators: %d: number of elements */
		$notes[] = sprintf( _n( '%d image, text or symbol was ignored; only shapes are used.', '%d images, texts or symbols were ignored; only shapes are used.', $context['ignored'], 'kbuilder' ), $context['ignored'] );
	}

	return array(
		'shapes' => $context['shapes'],
		'notes'  => $notes,
	);
}

/**
 * [x, y, width, height] from the viewBox, or from width/height when there's none.
 */
function kbuilder_svg_viewbox( DOMElement $root ) {
	$box = kbuilder_svg_numbers( $root->getAttribute( 'viewBox' ) );
	if ( 4 === count( $box ) && $box[2] > 0 && $box[3] > 0 ) {
		return $box;
	}

	$width  = (float) $root->getAttribute( 'width' );
	$height = (float) $root->getAttribute( 'height' );

	return $width > 0 && $height > 0 ? array( 0, 0, $width, $height ) : null;
}

function kbuilder_svg_walk( DOMElement $parent, array $matrix, array $inherited, array &$context ) {
	static $skip = array( 'defs', 'clipPath', 'mask', 'symbol', 'pattern', 'marker', 'style', 'title', 'desc', 'metadata', 'linearGradient', 'radialGradient', 'filter', 'script', 'foreignObject' );
	static $ignored = array( 'image', 'text', 'use' );

	foreach ( $parent->childNodes as $node ) {
		if ( ! $node instanceof DOMElement || in_array( $node->localName, $skip, true ) ) {
			continue;
		}
		// Editor data (Illustrator's i:pgf, Inkscape's sodipodi:…) lives in other namespaces.
		if ( $node->namespaceURI && 'http://www.w3.org/2000/svg' !== $node->namespaceURI ) {
			continue;
		}

		$style = kbuilder_svg_style( $node, $context['classes'] );
		if ( ( isset( $style['display'] ) && 'none' === $style['display'] ) || ( isset( $style['visibility'] ) && 'hidden' === $style['visibility'] ) ) {
			continue;
		}

		$state = array(
			'fill'      => isset( $style['fill'] ) ? $style['fill'] : $inherited['fill'],
			'fill-rule' => isset( $style['fill-rule'] ) ? $style['fill-rule'] : $inherited['fill-rule'],
		);
		$local = kbuilder_svg_multiply( $matrix, kbuilder_svg_transform( $node->getAttribute( 'transform' ) ) );

		if ( in_array( $node->localName, array( 'g', 'a', 'switch' ), true ) ) {
			kbuilder_svg_walk( $node, $local, $state, $context );
			continue;
		}
		if ( in_array( $node->localName, $ignored, true ) ) {
			$context['ignored']++;
			continue;
		}

		$d = kbuilder_svg_shape_path( $node );
		if ( null === $d ) {
			continue;
		}
		if ( 'none' === $state['fill'] ) {
			$context['unfilled']++;
			continue;
		}
		if ( 'rect' === $node->localName && kbuilder_svg_covers_canvas( $node, kbuilder_svg_transform( $node->getAttribute( 'transform' ) ), $parent, $context['canvas'] ) ) {
			$context['background']++;
			continue;
		}

		$shape = array( 'd' => $d );
		if ( ! kbuilder_svg_is_identity( $local ) ) {
			$shape['m'] = array_map(
				function ( $value ) {
					return round( $value, 6 );
				},
				$local
			);
		}
		if ( 'evenodd' === $state['fill-rule'] ) {
			$shape['r'] = 'evenodd';
		}
		$context['shapes'][] = $shape;
	}
}

/**
 * Path data for a basic shape element, or null when it isn't a fillable shape.
 */
function kbuilder_svg_shape_path( DOMElement $el ) {
	$attr = function ( $name ) use ( $el ) {
		return (float) $el->getAttribute( $name );
	};
	$n    = 'kbuilder_svg_number';

	switch ( $el->localName ) {
		case 'path':
			$d = trim( preg_replace( '/\s+/', ' ', $el->getAttribute( 'd' ) ) );
			return preg_match( '/^[Mm][MmZzLlHhVvCcSsQqTtAa0-9eE\s,.+-]*$/', $d ) ? $d : null;

		case 'rect':
			$x = $attr( 'x' );
			$y = $attr( 'y' );
			$w = $attr( 'width' );
			$h = $attr( 'height' );
			if ( $w <= 0 || $h <= 0 ) {
				return null;
			}
			$rx = $el->hasAttribute( 'rx' ) ? $attr( 'rx' ) : $attr( 'ry' );
			$ry = $el->hasAttribute( 'ry' ) ? $attr( 'ry' ) : $rx;
			$rx = min( max( $rx, 0 ), $w / 2 );
			$ry = min( max( $ry, 0 ), $h / 2 );
			if ( ! $rx || ! $ry ) {
				return sprintf( 'M%s %sH%sV%sH%sZ', $n( $x ), $n( $y ), $n( $x + $w ), $n( $y + $h ), $n( $x ) );
			}
			$arc = sprintf( 'A%s %s 0 0 1 ', $n( $rx ), $n( $ry ) );
			return sprintf(
				'M%1$s %2$sH%3$s%9$s%4$s %5$sV%6$s%9$s%3$s %7$sH%1$s%9$s%8$s %6$sV%5$s%9$s%1$s %2$sZ',
				$n( $x + $rx ),
				$n( $y ),
				$n( $x + $w - $rx ),
				$n( $x + $w ),
				$n( $y + $ry ),
				$n( $y + $h - $ry ),
				$n( $y + $h ),
				$n( $x ),
				$arc
			);

		case 'circle':
		case 'ellipse':
			$cx = $attr( 'cx' );
			$cy = $attr( 'cy' );
			$rx = 'circle' === $el->localName ? $attr( 'r' ) : $attr( 'rx' );
			$ry = 'circle' === $el->localName ? $rx : $attr( 'ry' );
			if ( $rx <= 0 || $ry <= 0 ) {
				return null;
			}
			$arc = sprintf( 'A%s %s 0 1 0 ', $n( $rx ), $n( $ry ) );
			return sprintf( 'M%1$s %2$s%4$s%3$s %2$s%4$s%1$s %2$sZ', $n( $cx - $rx ), $n( $cy ), $n( $cx + $rx ), $arc );

		case 'polygon':
		case 'polyline':
			$points = kbuilder_svg_numbers( $el->getAttribute( 'points' ) );
			if ( count( $points ) < 6 ) {
				return null;
			}
			$pairs = array();
			for ( $i = 0; $i + 1 < count( $points ); $i += 2 ) {
				$pairs[] = $n( $points[ $i ] ) . ' ' . $n( $points[ $i + 1 ] );
			}
			return 'M' . implode( 'L', $pairs ) . 'Z';
	}

	return null;
}

/**
 * Whether a rect fills the whole canvas, like a Figma frame or an artboard background.
 */
function kbuilder_svg_covers_canvas( DOMElement $rect, array $own, DOMElement $parent, array $canvas ) {
	if ( ! kbuilder_svg_is_identity( $own ) || 'svg' !== $parent->localName ) {
		return false;
	}
	list( $x, $y, $width, $height ) = $canvas;

	return (float) $rect->getAttribute( 'x' ) <= $x + 1
		&& (float) $rect->getAttribute( 'y' ) <= $y + 1
		&& (float) $rect->getAttribute( 'width' ) >= $width - 2
		&& (float) $rect->getAttribute( 'height' ) >= $height - 2;
}

/**
 * Styles that matter here (fill, fill-rule, display, visibility), from
 * presentation attributes, then classes, then the style attribute.
 */
function kbuilder_svg_style( DOMElement $el, array $classes ) {
	$style = array();

	foreach ( array( 'fill', 'fill-rule', 'display', 'visibility' ) as $property ) {
		if ( $el->hasAttribute( $property ) ) {
			$style[ $property ] = strtolower( trim( $el->getAttribute( $property ) ) );
		}
	}
	foreach ( preg_split( '/\s+/', trim( $el->getAttribute( 'class' ) ), -1, PREG_SPLIT_NO_EMPTY ) as $class ) {
		if ( isset( $classes[ $class ] ) ) {
			$style = array_merge( $style, $classes[ $class ] );
		}
	}

	return array_merge( $style, kbuilder_svg_declarations( $el->getAttribute( 'style' ) ) );
}

/**
 * Class rules from <style> blocks, as Illustrator writes them: `.cls-1, .cls-2 { fill: none; }`.
 */
function kbuilder_svg_class_styles( DOMDocument $dom ) {
	$classes = array();

	foreach ( $dom->getElementsByTagName( 'style' ) as $style ) {
		if ( ! preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $style->textContent, $rules, PREG_SET_ORDER ) ) {
			continue;
		}
		foreach ( $rules as $rule ) {
			$declarations = kbuilder_svg_declarations( $rule[2] );
			foreach ( explode( ',', $rule[1] ) as $selector ) {
				if ( preg_match( '/^\s*\.([\w-]+)\s*$/', $selector, $match ) ) {
					$previous                = isset( $classes[ $match[1] ] ) ? $classes[ $match[1] ] : array();
					$classes[ $match[1] ] = array_merge( $previous, $declarations );
				}
			}
		}
	}

	return $classes;
}

function kbuilder_svg_declarations( $css ) {
	$declarations = array();

	foreach ( explode( ';', (string) $css ) as $declaration ) {
		$parts = explode( ':', $declaration, 2 );
		if ( 2 === count( $parts ) ) {
			$declarations[ strtolower( trim( $parts[0] ) ) ] = strtolower( trim( $parts[1] ) );
		}
	}

	return array_intersect_key( $declarations, array_flip( array( 'fill', 'fill-rule', 'display', 'visibility' ) ) );
}

/**
 * Matrix [a, b, c, d, e, f] for an SVG transform list.
 */
function kbuilder_svg_transform( $value ) {
	$matrix = array( 1, 0, 0, 1, 0, 0 );

	if ( ! preg_match_all( '/(matrix|translate|scale|rotate|skewX|skewY)\s*\(([^)]*)\)/i', (string) $value, $calls, PREG_SET_ORDER ) ) {
		return $matrix;
	}

	foreach ( $calls as $call ) {
		$v    = kbuilder_svg_numbers( $call[2] );
		$step = null;

		switch ( strtolower( $call[1] ) ) {
			case 'matrix':
				$step = 6 === count( $v ) ? $v : null;
				break;
			case 'translate':
				$step = array( 1, 0, 0, 1, isset( $v[0] ) ? $v[0] : 0, isset( $v[1] ) ? $v[1] : 0 );
				break;
			case 'scale':
				$scale_x = isset( $v[0] ) ? $v[0] : 1;
				$step    = array( $scale_x, 0, 0, isset( $v[1] ) ? $v[1] : $scale_x, 0, 0 );
				break;
			case 'rotate':
				$angle = deg2rad( isset( $v[0] ) ? $v[0] : 0 );
				$step  = array( cos( $angle ), sin( $angle ), -sin( $angle ), cos( $angle ), 0, 0 );
				if ( isset( $v[2] ) ) {
					$step = kbuilder_svg_multiply( kbuilder_svg_multiply( array( 1, 0, 0, 1, $v[1], $v[2] ), $step ), array( 1, 0, 0, 1, -$v[1], -$v[2] ) );
				}
				break;
			case 'skewx':
				$step = array( 1, 0, tan( deg2rad( isset( $v[0] ) ? $v[0] : 0 ) ), 1, 0, 0 );
				break;
			case 'skewy':
				$step = array( 1, tan( deg2rad( isset( $v[0] ) ? $v[0] : 0 ) ), 0, 1, 0, 0 );
				break;
		}

		if ( $step ) {
			$matrix = kbuilder_svg_multiply( $matrix, $step );
		}
	}

	return $matrix;
}

function kbuilder_svg_multiply( array $m, array $n ) {
	return array(
		$m[0] * $n[0] + $m[2] * $n[1],
		$m[1] * $n[0] + $m[3] * $n[1],
		$m[0] * $n[2] + $m[2] * $n[3],
		$m[1] * $n[2] + $m[3] * $n[3],
		$m[0] * $n[4] + $m[2] * $n[5] + $m[4],
		$m[1] * $n[4] + $m[3] * $n[5] + $m[5],
	);
}

function kbuilder_svg_is_identity( array $m ) {
	return abs( $m[0] - 1 ) < 1e-6 && abs( $m[1] ) < 1e-6 && abs( $m[2] ) < 1e-6
		&& abs( $m[3] - 1 ) < 1e-6 && abs( $m[4] ) < 1e-3 && abs( $m[5] ) < 1e-3;
}

/**
 * Numbers in an SVG list, which may be separated by spaces, commas or just a sign ("10-5").
 */
function kbuilder_svg_numbers( $value ) {
	preg_match_all( '/[-+]?(?:\d*\.\d+|\d+\.?)(?:e[-+]?\d+)?/i', (string) $value, $matches );

	return array_map( 'floatval', $matches[0] );
}

function kbuilder_svg_number( $value ) {
	$value = rtrim( rtrim( number_format( (float) $value, 2, '.', '' ), '0' ), '.' );

	return '-0' === $value ? '0' : $value;
}
