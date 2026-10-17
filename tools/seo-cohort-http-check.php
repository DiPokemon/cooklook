<?php
/** Check real local cohort pages: php tools/seo-cohort-http-check.php > build/seo-cohort-http.json */
if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 404 );
	exit;
}

function cohort_http_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function cohort_http_text( $value ) {
	return trim( preg_replace( '/\s+/u', ' ', $value ) );
}

$cohort = json_decode( file_get_contents( dirname( __DIR__ ) . '/docs/seo-editorial-cohort.json' ), true, 512, JSON_THROW_ON_ERROR );
$batch = json_decode( file_get_contents( dirname( __DIR__ ) . '/docs/seo-editorial-batch.json' ), true, 512, JSON_THROW_ON_ERROR );
$excerpts = array_column( $batch['recipes'], 'excerpt', 'id' );
$results = array();
$context = stream_context_create( array( 'http' => array(
	'method' => 'GET', 'timeout' => 15, 'follow_location' => 0, 'ignore_errors' => true,
	'header' => "User-Agent: Cooklook-Cohort-Check/1.0\r\nConnection: close\r\n",
) ) );
foreach ( $cohort['pairs'] as $pair ) {
	foreach ( array( 'treatment', 'control' ) as $group ) {
		$entry = $pair[ $group ];
		$url = $entry['url'];
		cohort_http_assert( 0 === strpos( $url, 'http://cooklook/recipe/' ), 'Only local recipe URLs are allowed.' );
		$html = file_get_contents( $url, false, $context, 0, 2097152 );
		cohort_http_assert( is_string( $html ) && strlen( $html ) < 2097152 && preg_match( '~^HTTP/\S+ 200\b~', $http_response_header[0] ?? '' ), 'HTTP 200 required: ' . $entry['id'] );
		cohort_http_assert( ! preg_match( '/(?:Fatal error|Warning):.*(?:PHP|wp-content|wp-includes)/i', $html ), 'PHP error in HTML: ' . $entry['id'] );
		$dom = new DOMDocument();
		@$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
		$xpath = new DOMXPath( $dom );
		$recipes = array();
		foreach ( $xpath->query( '//script[@type="application/ld+json"]' ) as $script ) {
			$data = json_decode( $script->textContent, true, 512, JSON_THROW_ON_ERROR );
			foreach ( $data['@graph'] ?? array( $data ) as $node ) {
				if ( in_array( 'Recipe', (array) ( $node['@type'] ?? array() ), true ) ) {
					$recipes[] = $node;
				}
			}
		}
		cohort_http_assert( count( $recipes ) === 1, 'Exactly one Recipe required: ' . $entry['id'] );
		$recipe = $recipes[0];
		$h1 = $xpath->query( '//h1' );
		cohort_http_assert( $h1->length === 1 && cohort_http_text( $h1->item( 0 )->textContent ) === cohort_http_text( $entry['title'] ), 'H1 mismatch: ' . $entry['id'] );
		cohort_http_assert( ( $recipe['author']['name'] ?? null ) === 'CookLook' && ! empty( $recipe['image'] ), 'Author/image mismatch: ' . $entry['id'] );
		cohort_http_assert( count( $recipe['recipeIngredient'] ?? array() ) === $entry['ingredient_count'] && count( $recipe['recipeInstructions'] ?? array() ) === $entry['step_count'], 'Ingredients/steps mismatch: ' . $entry['id'] );
		$description = $xpath->query( '//meta[@name="description"]/@content' );
		$intro = $xpath->query( '//div[contains(concat(" ", normalize-space(@class), " "), " recipe_description ")]' );
		cohort_http_assert( $description->length === 1 && $intro->length === 1 && cohort_http_text( $intro->item( 0 )->textContent ) === cohort_http_text( $description->item( 0 )->value ) && $description->item( 0 )->value === ( $recipe['description'] ?? null ), 'HTML/meta/Recipe descriptions differ: ' . $entry['id'] );
		if ( 'treatment' === $group ) {
			cohort_http_assert( $description->item( 0 )->value === $excerpts[ $entry['id'] ], 'Edited excerpt mismatch: ' . $entry['id'] );
		}
		$robots = $xpath->query( '//meta[@name="robots"]/@content' );
		cohort_http_assert( $robots->length === 1 && preg_match( '/\bnoindex\b/i', $robots->item( 0 )->value ), 'Local noindex missing: ' . $entry['id'] );
		$canonicals = array();
		foreach ( $xpath->query( '//link[@rel="canonical"]/@href' ) as $canonical ) {
			$canonicals[] = $canonical->value;
			cohort_http_assert( rawurldecode( $canonical->value ) === rawurldecode( $url ), 'Unexpected canonical: ' . $entry['id'] );
		}
		cohort_http_assert( count( $canonicals ) <= 1, 'Duplicate canonical: ' . $entry['id'] );
		$results[] = array( 'pair_id' => $pair['pair_id'], 'group' => $group, 'id' => $entry['id'], 'url' => $url,
			'http_status' => 200, 'h1_matches' => true, 'recipe_nodes' => 1, 'ingredients_steps_match' => true,
			'descriptions_agree' => true, 'description_sha256' => hash( 'sha256', $recipe['description'] ),
			'local_noindex' => true, 'canonical' => $canonicals );
	}
}
echo json_encode( array( 'checked_at' => gmdate( 'c' ), 'site' => $cohort['site'], 'passed' => true,
	'method' => 'Sequential unauthenticated local GETs, no redirects, cookies or JavaScript. Actual HTML/JSON-LD checked; no production crawling or search observations.',
	'pages' => $results ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
