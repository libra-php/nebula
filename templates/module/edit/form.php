<form enctype="multipart/form-data" class="pt-2">
	<?= $csrf() ?>
	<?php if ($data): ?>
		<?php foreach ($data as $datum) : ?>
			<div class="row mt-2">
				<div class="col-3 form-label d-flex align-items-center">
					<span title="<?= $datum->title ?>"><?= $datum->title ?></span>
				</div>
				<div class="col-9 form-content d-flex align-items-center">
					<div class="w-100">
						<?= $control($datum->column, $old($datum->column, $datum->value), $datum->title) ?>
						<?= $request_errors($datum->column) ?>
					</div>
				</div>
			</div>
		<?php endforeach ?>
	<?php endif ?>
	<div class="mt-2">
		<button hx-patch="" hx-indicator="#request-progress" name="save" hx-swap="outerHTML" hx-target="#view" hx-select="#view" id="edit-save" type="submit" class="btn btn-primary">Save</button>
		<?php if ($show_back): ?>
		<button hx-get="/admin/<?= $module ?>/" hx-indicator="#request-progress" hx-swap="outerHTML" hx-target="#view" hx-select="#view" class="btn btn-secondary ms-1">Back</button>
		<?php endif ?>
	</div>
</form>
