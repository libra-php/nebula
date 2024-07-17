<section id="actions" class="mb-3">
	<?php if ($actions['show_create_action']) : ?>
		<button type="button" hx-get="/admin/<?= $module ?>/create" hx-indicator="#request-progress" hx-swap="outerHTML" hx-select="#view" hx-target="#view" class="btn btn-success btn-sm">Create Record</button>
	<?php endif ?>
	<?php if ($actions['show_export_action']) : ?>
	<a type="button" href="?export_csv" hx-indicator="#request-progress" class="btn btn-success btn-sm">Export CSV</a>
	<?php endif ?>
</section>
<section id="filters">
	<?= $filters['search'] ?>
	<?= $filters['link'] ?>
</section>
<section id="table" class="table-responsive">
	<?= $table ?>
</section>
<section id="pagination">
	<?= $pagination ?>
</section>
