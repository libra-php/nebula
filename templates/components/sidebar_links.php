<?php
    foreach ($links as $child): ?>
        <li class="sidebar-link"><a href="<?=str_replace(".php", '', $child['link'])?>"
                <?php if ($child['label'] === "Sign Out"): ?>hx-boost=false<?php endif ?>
                hx-indicator="#request-progress"
                title="<?=$child['label']?>"
                onClick="hideSidebar(); htmx.trigger('.sidebar-link', 'htmx:xhr:abort');"
                data-title="<?=$child['label']?>"
                data-parent="<?=$parent_link?>"
                style="padding-left: <?=$depth?>px;"
                class="link link-dark rounded truncate">
                <?=$child['label']?>
            </a></li>
        <?php if (!empty($child['children'])) { echo template("components/sidebar_links.php", ["links" => $child['children'], "parent_link" => $parent_link, "depth" => $depth + 10]); } ?>
    <?php endforeach;

