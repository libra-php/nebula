<?php if ($tracks): ?>
<div class="p-2 d-flex">
	<button class="btn btn-sm btn-outline-success"
		hx-get="/search/playlist"
		hx-swap="none">Add to playlist</button>
</div>
<?php endif ?>

<?php foreach ($tracks as $track) : ?>
	<?=template("muse/tracks/row.php", ["track" => $track])?>
<?php endforeach ?>

<?php if (!$tracks): ?>
	<p class="text-danger p-2">No tracks found</p>
<?php endif ?>