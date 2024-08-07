<form>
	<?= $csrf() ?>
	<table class="table">
		<thead class="bg-dark">
			<tr>
				<?php foreach ($columns as $header => $column) : ?>
					<th scope="col">
						<a class="<?=($column == $order_by ? 'active' : '')?>" href="?order=<?=$column?>&sort=<?php if ($column == $order_by): ?><?=($sort === 'ASC' ? 'DESC' : 'ASC')?><?php else: ?>DESC<?php endif ?>">
							<?= $header ?>
							<?php if ($column == $order_by): ?>
								<?=($sort === "ASC" ? "▴" : "▾")?>
							<?php endif ?>
						</a>
					</th>
				<?php endforeach ?>
				<?php if ($show_row_actions) : ?>
					<th></th>
				<?php endif ?>
			</tr>
		</thead>
		<tbody>
			<?php if ($data) : ?>
				<?php foreach ($data as $row) : ?>
					<tr>
						<?php foreach ($row as $column => $value) : ?>
							<td class="align-top">
								<?php if ($link_column && $column === $link_column): ?>
									<a href="/admin/<?= $module ?>/<?= $row->$primary_key ?>">
										<?= $format($column, $value) ?>
									</a>
								<?php else: ?>
									<?= $format($column, $value) ?>
								<?php endif ?>
							</td>
						<?php endforeach ?>
						<?php if ($show_row_actions) : ?>
							<td class="row-action align-top">
								<div class="w-100 d-flex justify-content-end">
									<?php if ($show_row_edit($row->$primary_key)) : ?>
										<button type="button" hx-get="/admin/<?= $module ?>/<?= $row->$primary_key ?>" hx-indicator="#request-progress" hx-swap="outerHTML" hx-select="#view" hx-target="#view" class="btn btn-sm btn-primary ms-1">Edit</button>
									<?php endif ?>
									<?php if ($show_row_delete($row->$primary_key)) : ?>
										<button type="button" hx-confirm="Are you sure you want to delete this record?" hx-delete="/admin/<?= $module ?>/<?= $row->$primary_key ?>" hx-indicator="#request-progress" hx-swap="outerHTML" hx-select="#view" hx-target="#view" class="btn btn-sm btn-danger ms-1">Delete</button>
									<?php endif ?>
								</div>
							</td>
						<?php endif ?>
					</tr>
				<?php endforeach ?>
			<?php else : ?>
				<tr>
					<td align="center" colspan="<?= count($columns) ?>"><em>There are no records</em></td>
				</tr>
			<?php endif ?>
		</tbody>
	</table>
</form>
