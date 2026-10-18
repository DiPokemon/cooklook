<?php
/** Deterministic encoding of existing banners. Requires Imagick with WebP support. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
$directory = dirname( __DIR__ ) . '/static/img/adv';
$manifest = array();
foreach ( array( 'main_categories', 'main_square_1', 'main_square_2', 'main_square_3', 'recipe_archive_1', 'recipe_page', 'recipe_page_square' ) as $name ) {
	$source = new Imagick( $directory . '/' . $name . '.jpg' );
	$manifest[ $name ] = array( 'width' => $source->getImageWidth(), 'height' => $source->getImageHeight(), 'sources' => array() );
	$widths = array_unique( array( min( 360, $source->getImageWidth() ), min( 720, $source->getImageWidth() ), $source->getImageWidth() ) );
	foreach ( $widths as $width ) {
		$image = clone $source;
		$image->resizeImage( $width, 0, Imagick::FILTER_LANCZOS, 1 );
		$image->setImageFormat( 'webp' );
		$image->setImageCompressionQuality( 82 );
		$image->stripImage();
		$file = $name . '-' . $width . '.webp';
		$image->writeImage( $directory . '/' . $file );
		$manifest[ $name ]['sources'][ $width ] = $file;
		$image->clear();
	}
	$source->clear();
}
file_put_contents( $directory . '/variants.json', json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
echo 'Built responsive WebP variants for ' . count( $manifest ) . " banners.\n";
