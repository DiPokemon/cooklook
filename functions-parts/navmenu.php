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
function filter_wp_nav_menu($nav_menu, $args) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML(mb_convert_encoding($nav_menu, 'HTML-ENTITIES', 'UTF-8'));
    $x = new DOMXPath($dom);
    foreach($x->query("//nav") as $node) {
        $node->setAttribute("itemscope", "");	
		$node->setAttribute("itemtype", "http://schema.org/SiteNavigationElement");	
    }
    foreach($x->query("//a") as $node) {
        $node->setAttribute("itemprop","url");
    }
    foreach($x->query("//li") as $node) {
        $node->setAttribute("itemprop","itemListElement");
		$node->setAttribute("itemscope","");
		$node->setAttribute("itemtype","http://schema.org/ItemList");
        $anchor = $x->query(".//a", $node)->item(0);
        if ($anchor) {
            $menuName = $anchor->nodeValue; // Имя пункта меню

            // Создаем и добавляем meta элемент
            $meta = $dom->createElement('meta');
            $meta->setAttribute("itemprop", "name");
            $meta->setAttribute("content", $menuName);
            $node->appendChild($meta);
        }
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
    $nav_menu = $dom->saveHTML();
    return $nav_menu;
}
add_filter('wp_nav_menu', 'filter_wp_nav_menu', 9999, 2);