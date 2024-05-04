<?php if ($show): ?>
	<?php foreach ($links as $idx => $link) : ?>
	<span class="badge filter-link <?=($current === $idx ? 'active' : '')?>"
		hx-get="?filter_link=<?=$idx?>" hx-swap="outerHTML" hx-target="#list" hx-select="#list" hx-trigger="click">
		<?= $link ?> [<span class="count_<?=$idx?>">...</span>]
	</span>
	<span hx-trigger="load" hx-get="?filter_count=<?= $idx ?>" hx-target=".count_<?=$idx?>">
	</span>
	<?php endforeach ?>
<?php endif ?>
