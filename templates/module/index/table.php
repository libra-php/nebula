<div class="table-responsive">
	<table class="table table-sm">
		<thead class="gradient-dark">
			<tr>
				<?php foreach ($headers as $header) : ?>
					<th scope="col">
						<?= $header ?>
					</th>
				<?php endforeach ?>
			</tr>
		</thead>
		<tbody>
			<?php if ($data) : ?>
				<?php foreach ($data as $row) : ?>
					<tr>
						<?php foreach ($row as $datum) : ?>
							<td>
								<?= $datum ?>
							</td>
						<?php endforeach ?>
					</tr>
				<?php endforeach ?>
			<?php else : ?>
				<tr>
					<td align="center" colspan="<?=count($headers)?>"><em>There are no records</em></td>
				</tr>
			<?php endif ?>
		</tbody>
	</table>
</div>