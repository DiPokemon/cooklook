<?php
/*
* Модальное окно с ингридиентами для фильтра
*/



?>

<div id="ingridients_overlay" class="ingridients_modal_overlay">
    <div class="ingridients_modal">
        <button id="close_ingridients" class="close_modal hide_mobile">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M13.3002 0.70998C13.2077 0.617276 13.0978 0.543728 12.9768 0.493547C12.8559 0.443366 12.7262 0.417535 12.5952 0.417535C12.4643 0.417535 12.3346 0.443366 12.2136 0.493547C12.0926 0.543728 11.9827 0.617276 11.8902 0.70998L7.00022 5.58998L2.11022 0.699979C2.01764 0.607397 1.90773 0.533957 1.78677 0.483852C1.6658 0.433747 1.53615 0.407959 1.40522 0.407959C1.27429 0.407959 1.14464 0.433747 1.02368 0.483852C0.902716 0.533957 0.792805 0.607397 0.700223 0.699979C0.607642 0.792561 0.534202 0.902472 0.484097 1.02344C0.433992 1.1444 0.408203 1.27405 0.408203 1.40498C0.408203 1.53591 0.433992 1.66556 0.484097 1.78652C0.534202 1.90749 0.607642 2.0174 0.700223 2.10998L5.59022 6.99998L0.700223 11.89C0.607642 11.9826 0.534202 12.0925 0.484097 12.2134C0.433992 12.3344 0.408203 12.464 0.408203 12.595C0.408203 12.7259 0.433992 12.8556 0.484097 12.9765C0.534202 13.0975 0.607642 13.2074 0.700223 13.3C0.792805 13.3926 0.902716 13.466 1.02368 13.5161C1.14464 13.5662 1.27429 13.592 1.40522 13.592C1.53615 13.592 1.6658 13.5662 1.78677 13.5161C1.90773 13.466 2.01764 13.3926 2.11022 13.3L7.00022 8.40998L11.8902 13.3C11.9828 13.3926 12.0927 13.466 12.2137 13.5161C12.3346 13.5662 12.4643 13.592 12.5952 13.592C12.7262 13.592 12.8558 13.5662 12.9768 13.5161C13.0977 13.466 13.2076 13.3926 13.3002 13.3C13.3928 13.2074 13.4662 13.0975 13.5163 12.9765C13.5665 12.8556 13.5922 12.7259 13.5922 12.595C13.5922 12.464 13.5665 12.3344 13.5163 12.2134C13.4662 12.0925 13.3928 11.9826 13.3002 11.89L8.41022 6.99998L13.3002 2.10998C13.6802 1.72998 13.6802 1.08998 13.3002 0.70998Z" />
            </svg>
        </button>

        <div class="ingridients_filter-header mobile_display">
            <button id="close_ingridients-mobile">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M12.5 5L7.5 10L12.5 15" stroke="#292D32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?= __('Назад', 'cooklook') ?>
            </button>
            <a href="#" class="clear_ingridients"><?= __('Очистить все', 'cooklook') ?></a>
        </div>

        <div class="ingridients_wrapper">
            <div class="ing_wrap">
                <label for="include_ingredients"><?= __('Включить ингридиенты', 'cooklook') ?></label>
                <!-- <input type="text" id="include_ingredients" name="include_ingredients" class="ingredient_select" multiple="multiple">    
                         -->
                <select id="include_ingredients" name="include_ingredients[]" multiple class="ingredient_select">
                    <?php foreach ($ingridients as $ingridient) : ?>
                        <option value="<?= esc_attr($ingridient->slug) ?>"><?= esc_html($ingridient->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
           
            <div class="ing_wrap">
                <label for="exclude_ingredients"><?= __('Икслючить ингридиенты', 'cooklook') ?></label>
                <select id="exclude_ingredients" name="exclude_ingredients[]" multiple class="ingredient_select">
                    <?php foreach ($ingridients as $ingridient) : ?>
                        <option value="<?= esc_attr($ingridient->slug) ?>"><?= esc_html($ingridient->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div id="popular_ingridients" class="popular_ingridients">
            <span class="popular_ingridients-title"><?= __('Популярные ингридиенты:', 'cooklook') ?></span>
            <div class="popular_ingridients-items">
                <?php
                    $popular_tags = get_terms(array(
                        'taxonomy' => 'recipe_tags',
                        'orderby' => 'count',
                        'order' => 'DESC',
                        'number' => 6,
                    ));
                    foreach ($popular_tags as $tag) : 
                ?>
                    <!-- <a class="popular_ingridients-item" href="javascript:void(0);" onclick="setIncludeIngridients('<?= $tag->name ?>')"><?= $tag->name ?></a> -->
                    <a class="popular_ingridients-item" href="javascript:void(0);" data-ingredient="<?= $tag->name ?>"> <?= $tag->name ?> </a>

                <?php endforeach; ?>
            </div>
        </div>

        <div class="ingridients_actions">
            <button id="ingridients_submit" class="btn_bg" type="submit">
                <?= __('Показать рецепты', 'cooklook') ?>
            </button>
            <button class="clear_ingridients hide_mobile">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M15.2492 4.75828C15.1721 4.68102 15.0805 4.61973 14.9797 4.57792C14.8789 4.5361 14.7708 4.51457 14.6617 4.51457C14.5526 4.51457 14.4445 4.5361 14.3437 4.57792C14.2429 4.61973 14.1513 4.68102 14.0742 4.75828L9.99921 8.82494L5.92421 4.74994C5.84706 4.67279 5.75547 4.61159 5.65466 4.56984C5.55386 4.52808 5.44582 4.50659 5.33671 4.50659C5.2276 4.50659 5.11956 4.52808 5.01876 4.56984C4.91795 4.61159 4.82636 4.67279 4.74921 4.74994C4.67206 4.82709 4.61086 4.91869 4.5691 5.01949C4.52735 5.12029 4.50586 5.22833 4.50586 5.33744C4.50586 5.44655 4.52735 5.55459 4.5691 5.6554C4.61086 5.7562 4.67206 5.84779 4.74921 5.92494L8.82421 9.99994L4.74921 14.0749C4.67206 14.1521 4.61086 14.2437 4.5691 14.3445C4.52735 14.4453 4.50586 14.5533 4.50586 14.6624C4.50586 14.7716 4.52735 14.8796 4.5691 14.9804C4.61086 15.0812 4.67206 15.1728 4.74921 15.2499C4.82636 15.3271 4.91795 15.3883 5.01876 15.43C5.11956 15.4718 5.2276 15.4933 5.33671 15.4933C5.44582 15.4933 5.55386 15.4718 5.65466 15.43C5.75547 15.3883 5.84706 15.3271 5.92421 15.2499L9.99921 11.1749L14.0742 15.2499C14.1514 15.3271 14.243 15.3883 14.3438 15.43C14.4446 15.4718 14.5526 15.4933 14.6617 15.4933C14.7708 15.4933 14.8789 15.4718 14.9797 15.43C15.0805 15.3883 15.1721 15.3271 15.2492 15.2499C15.3264 15.1728 15.3876 15.0812 15.4293 14.9804C15.4711 14.8796 15.4926 14.7716 15.4926 14.6624C15.4926 14.5533 15.4711 14.4453 15.4293 14.3445C15.3876 14.2437 15.3264 14.1521 15.2492 14.0749L11.1742 9.99994L15.2492 5.92494C15.5659 5.60828 15.5659 5.07494 15.2492 4.75828Z" fill="#828282"/>
                </svg>
                <?= __('Очистить все', 'cooklook') ?>
            </button>
        </div>
        
    </div>
</div>
