<?php foreach ($links as $child): ?>
    <li class="sidebar-link"
        <?php if ($child['label'] === "Sign Out"): ?>hx-boost=false<?php endif ?>
        hx-indicator="#request-progress"
        hx-sync="form:abort">
        <a href="<?=str_replace(".php", '', $child['link'])?>"
            title="<?=$child['label']?>"
            onClick="hideSidebar();"
            data-title="<?=$child['label']?>"
            data-parent="<?=$parent_link?>"
            style="padding-left: <?=$depth?>px;"
            class="link link-dark rounded truncate">
            <?=$child['label']?>
        </a>
    </li>
        <?php if (!empty($child['children'])) { echo template("components/sidebar_links.php", ["links" => $child['children'], "parent_link" => $parent_link, "depth" => $depth + 10]); } ?>
    <?php endforeach;

