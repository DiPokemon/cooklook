<?php
if (! defined ('ABSPATH')){
    exit;
}

//Add classes for LI and A in nav menu + integrate Schema.ORG

//add class for LI in nav menu
function add_additional_class_on_li($classes, $item, $args) {
    if(isset($args->add_li_class)) {
        $classes[] = $args->add_li_class;
    }
    return $classes;
}
add_filter('nav_menu_css_class', 'add_additional_class_on_li', 1, 3);

//add class for <A> in nav menu
function add_menu_link_class( $atts, $item, $args ) {
    if (property_exists($args, 'link_class')) {
      $atts['class'] = $args->link_class;
    }
    return $atts;
  }
  add_filter( 'nav_menu_link_attributes', 'add_menu_link_class', 1, 3 );

/* add Schema.Org in NavMenu */
function filter_wp_nav_menu($nav_menu,$args) {
    global $schemaURL;
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $nav_menu);
    $x = new DOMXPath($dom);
    foreach($x->query("//a") as $node) {
        $node->setAttribute("itemprop","url");
    }
    foreach($x->query("//li") as $node) {
        $node->setAttribute("itemprop","itemListElement");
		$node->setAttribute("itemscope","");
		$node->setAttribute("itemtype","http://schema.org/ItemList");
    }
    foreach($x->query("//ul") as $node) {        
        $node->setAttribute("itemprop","about");
		$node->setAttribute("itemscope","");
		$node->setAttribute("itemtype","http://schema.org/ItemList");
    }
    foreach($x->query('//ul[contains(@class,"sub-menu")]') as $node) {        
        $node->setAttribute("itemprop","itemListElement");
		$node->setAttribute("itemscope","");
		$node->setAttribute("itemtype","http://schema.org/ItemList");
    }
    foreach($x->query("//nav") as $node) {
        $node->setAttribute("itemscope", "");	
		$node->setAttribute("itemtype", "http://schema.org/SiteNavigationElement");	
    }	
    $nav_menu = $node->c14n();
    return $nav_menu ;
}
add_filter( 'wp_nav_menu', 'filter_wp_nav_menu', 10, 2 );