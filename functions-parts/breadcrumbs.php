<?php
function breadcrumbs() {
    if (!defined('ABSPATH')) {
        exit;
    }

    // Опции
    $text['home']     = 'Главная';
    $text['category'] = '%s';
    $text['search']   = 'Результаты поиска по запросу "%s"';
    $text['tag']      = 'Записи с тегом "%s"';
    $text['author']   = 'Статьи автора %s';
    $text['404']      = 'Ошибка 404';
    $text['page']     = 'Страница %s';
    $text['cpage']    = 'Страница комментариев %s';

    $wrap_before    = '<div class="page-header__breadcrumbs" itemscope itemtype="http://schema.org/BreadcrumbList">';
    $wrap_after     = '</div><!-- .breadcrumbs -->';
    $sep            = '<span class="breadcrumbs__separator"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6.66797 5.33325L9.33464 7.99992L6.66797 10.6666" stroke="#292D32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
    $before         = '<span class="breadcrumbs__current">';
    $after          = '</span>';
    $show_on_home   = 0;
    $show_home_link = 1;
    $show_current   = 1;
    $show_last_sep  = 1;

    global $post;
    $home_url       = home_url('/');
    $link           = '<span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">';
    $link          .= '<a class="breadcrumbs__link" href="%1$s" itemprop="item"><span itemprop="name">%2$s</span></a>';
    $link          .= '<meta itemprop="position" content="%3$s" />';
    $link          .= '</span>';
    $home_link      = sprintf($link, $home_url, $text['home'], 1);

    if (is_home() || is_front_page()) {
        if ($show_on_home) echo $wrap_before . $home_link . $wrap_after;
    } else {
        $position = 0;
        echo $wrap_before;
        if ($show_home_link) {
            $position += 1;
            echo $home_link;
        }

        if (is_category()) {
            // Ваш существующий код для категории
        } elseif (is_single() && 'recipe' == get_post_type()) {
            // Обработка записи типа recipe
            $post_type = get_post_type_object(get_post_type());
            $position += 1;
            if ($position > 1) echo $sep;
            echo sprintf($link, get_post_type_archive_link($post_type->name), $post_type->labels->name, $position);

            $terms = get_the_terms($post->ID, 'recipe_category');
            if ($terms && !is_wp_error($terms)) {
                $main_term = array_shift($terms);
                $parents = get_ancestors($main_term->term_id, 'recipe_category');
                $parents = array_reverse($parents);

                foreach ($parents as $parent) {
                    $term = get_term($parent, 'recipe_category');
                    $position += 1;
                    echo $sep . sprintf($link, get_term_link($term), $term->name, $position);
                }
                $position += 1;
                echo $sep . sprintf($link, get_term_link($main_term), $main_term->name, $position);
            }

            if (get_query_var('cpage')) {
                $position += 1;
                echo $sep . sprintf($link, get_permalink(), get_the_title(), $position);
                echo $sep . $before . sprintf($text['cpage'], get_query_var('cpage')) . $after;
            } else {
                if ($show_current) echo $sep . $before . get_the_title() . $after;
                elseif ($show_last_sep) echo $sep;
            }
        } elseif (is_post_type_archive('recipe')) {
            // Ваш существующий код для архивов recipe
            $position += 1;
            if ($position > 1) echo $sep;
            echo $before . __('Каталог рецептов', 'cooklook') . $after;
        } elseif (is_tax('recipe_category')) {
            // Ваш существующий код для категорий recipe
            $term = get_term_by('slug', get_query_var('term'), 'recipe_category');
            if ($term) {
                $position += 1;
                echo $sep . sprintf($link, get_post_type_archive_link('recipe'), __('Каталог рецептов', 'cooklook'), $position);
                $parents = get_ancestors($term->term_id, 'recipe_category');
                foreach (array_reverse($parents) as $parent) {
                    $term = get_term($parent, 'recipe_category');
                    $position += 1;
                    echo $sep . sprintf($link, get_term_link($term), $term->name, $position);
                }
                if ($show_current) {
                    $position += 1;
                    echo $sep . $before . single_term_title('', false) . $after;
                } elseif ($show_last_sep) echo $sep;
            }
        } else {
            if (is_search()) {
                if (get_query_var('paged')) {
                    $position += 1;
                    if ($show_home_link) echo $sep;
                    echo sprintf($link, $home_url . '?s=' . get_search_query(), sprintf($text['search'], get_search_query()), $position);
                    echo $sep . $before . sprintf($text['page'], get_query_var('paged')) . $after;
                } else {
                    if ($show_current) {
                        if ($position >= 1) echo $sep;
                        echo $before . sprintf($text['search'], get_search_query()) . $after;
                    } elseif ($show_last_sep) echo $sep;
                }
            } elseif (is_year()) {
                if ($show_home_link && $show_current) echo $sep;
                if ($show_current) echo $before . get_the_time('Y') . $after;
                elseif ($show_home_link && $show_last_sep) echo $sep;
            } elseif (is_month()) {
                if ($show_home_link) echo $sep;
                $position += 1;
                echo sprintf($link, get_year_link(get_the_time('Y')), get_the_time('Y'), $position);
                if ($show_current) echo $sep . $before . get_the_time('F') . $after;
                elseif ($show_last_sep) echo $sep;
            } elseif (is_day()) {
                if ($show_home_link) echo $sep;
                $position += 1;
                echo sprintf($link, get_year_link(get_the_time('Y')), get_the_time('Y'), $position) . $sep;
                $position += 1;
                echo sprintf($link, get_month_link(get_the_time('Y'), get_the_time('m')), get_the_time('F'), $position);
                if ($show_current) echo $sep . $before . get_the_time('d') . $after;
                elseif ($show_last_sep) echo $sep;
            } elseif (is_single() && !is_attachment()) {
                if (get_post_type() != 'post') {
                    $post_type = get_post_type_object(get_post_type());
                    $position += 1;
                    if ($position > 1) echo $sep;
                    echo sprintf($link, get_post_type_archive_link($post_type->name), $post_type->labels->name, $position);
                    if ($show_current) echo $sep . $before . get_the_title() . $after;
                    elseif ($show_last_sep) echo $sep;
                } else {
                    $cat = get_the_category();
                    if ($cat) {
                        $cat = $cat[0];
                        $parents = get_category_parents($cat, true, $sep);
                        if (!$show_current || get_query_var('cpage')) $parents = preg_replace("#^(.+)$sep$#", "$1", $parents);
                        echo $parents;
                        if (get_query_var('cpage')) {
                            echo $sep . sprintf($link, get_permalink(), get_the_title(), $position);
                            echo $sep . $before . sprintf($text['cpage'], get_query_var('cpage')) . $after;
                        } else {
                            if ($show_current) echo $before . get_the_title() . $after;
                            elseif ($show_last_sep) echo $sep;
                        }
                    }
                }
            } elseif (is_post_type_archive()) {
                $post_type = get_post_type_object(get_post_type());
                if (get_query_var('paged')) {
                    $position += 1;
                    if ($position > 1) echo $sep;
                    echo sprintf($link, get_post_type_archive_link($post_type->name), $post_type->label, $position);
                    echo $sep . $before . sprintf($text['page'], get_query_var('paged')) . $after;
                } else {
                    if ($show_home_link && $show_current) echo $sep;
                    if ($show_current) echo $before . $post_type->label . $after;
                    elseif ($show_home_link && $show_last_sep) echo $sep;
                }
            } elseif (is_attachment()) {
                $parent = get_post($parent_id);
                $cat = get_the_category($parent->ID);
                if ($cat) {
                    $cat = $cat[0];
                    $parents = get_category_parents($cat, true, $sep);
                    if (!$show_current || get_query_var('cpage')) $parents = preg_replace("#^(.+)$sep$#", "$1", $parents);
                    echo $parents;
                    $position += 1;
                    echo $sep . sprintf($link, get_permalink($parent), $parent->post_title, $position);
                    if ($show_current) echo $sep . $before . get_the_title() . $after;
                    elseif ($show_last_sep) echo $sep;
                }
            } elseif (is_page() && !$parent_id) {
                if ($show_home_link && $show_current) echo $sep;
                if ($show_current) echo $before . get_the_title() . $after;
                elseif ($show_home_link && $show_last_sep) echo $sep;
            } elseif (is_page() && $parent_id) {
                $parents = get_post_ancestors(get_the_ID());
                foreach (array_reverse($parents) as $pageID) {
                    $position += 1;
                    if ($position > 1) echo $sep;
                    echo sprintf($link, get_page_link($pageID), get_the_title($pageID), $position);
                }
                if ($show_current) echo $sep . $before . get_the_title() . $after;
                elseif ($show_last_sep) echo $sep;
            } elseif (is_tag()) {
                if (get_query_var('paged')) {
                    $position += 1;
                    $tagID = get_query_var('tag_id');
                    echo $sep . sprintf($link, get_tag_link($tagID), single_tag_title('', false), $position);
                    echo $sep . $before . sprintf($text['page'], get_query_var('paged')) . $after;
                } else {
                    if ($show_home_link && $show_current) echo $sep;
                    if ($show_current) echo $before . sprintf($text['tag'], single_tag_title('', false)) . $after;
                    elseif ($show_home_link && $show_last_sep) echo $sep;
                }
            } elseif (is_author()) {
                $author = get_userdata(get_query_var('author'));
                if (get_query_var('paged')) {
                    $position += 1;
                    echo $sep . sprintf($link, get_author_posts_url($author->ID), sprintf($text['author'], $author->display_name), $position);
                    echo $sep . $before . sprintf($text['page'], get_query_var('paged')) . $after;
                } else {
                    if ($show_home_link && $show_current) echo $sep;
                    if ($show_current) echo $before . sprintf($text['author'], $author->display_name) . $after;
                    elseif ($show_last_sep) echo $sep;
                }
            } elseif (is_404()) {
                if ($show_home_link && $show_current) echo $sep;
                if ($show_current) echo $before . $text['404'] . $after;
                elseif ($show_last_sep) echo $sep;
            } elseif (has_post_format() && !is_singular()) {
                if ($show_home_link && $show_current) echo $sep;
                echo get_post_format_string(get_post_format());
            }
        }
        echo $wrap_after;
    }
}
?>
