<nav id="sidebar" class="d-none d-sm-block" hx-boost="true" hx-target="#view" hx-select="#view" hx-swap="outerHTML show:no-scroll">
	<div class="mb-3">
		<input type="search" class="form-control" id="filter" placeholder="Filter" tabindex="-1">
	</div>

	<?php if (!empty($most_visited)):  ?>
	<div id="most-visited">
	<h5 class="ps-3 fw-bold">Most visited</h5>
	<ul class="list-unstyled ps-0">
	<?php foreach ($most_visited as $link): ?>
		<li class="nav-link">
			<a hx-sync="form:abort" class="link link-dark rounded truncate" href="<?=$link->path?>" alt="link"><?=$link->title?></a>
		</li>
	<?php endforeach ?>
	</ul>
	</div>
	<?php endif ?>

	<div class="flex-shrink-0">
		<ul class="list-unstyled ps-0">
			<?php foreach ($links as $key => $link): ?>
			<?php if (!empty($link['children'])): ?>
			<li class="mb-1">
				<button class="btn btn-toggle align-items-center rounded parent-link"
					data-bs-toggle="collapse"
					data-bs-target="#link-<?=$key?>"
					aria-expanded="false">
					<?=$link['label']?>
				</button>
				<div class="collapse submenu" id="link-<?=$key?>">
					<ul class="btn-toggle-nav fw-normal sidebar-links pb-1 small">
						<?= template("components/sidebar_links.php", ["links" => $link["children"], "parent_link" => $link["label"], "depth" => 0]) ?>
					</ul>
				</div>
			</li>
			<?php endif ?>
			<?php endforeach ?>
		</ul>
	</div>
</nav>
