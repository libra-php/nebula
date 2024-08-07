<nav id="breadcrumbs" aria-label="breadcrumb" class="mt-2 container-fluid" hx-boost="true" hx-target="main" hx-select="main" hx-sync="this:abort" hx-swap="outerHTML show:no-scroll" hx-indicator="#request-progress" >
  <ol class="breadcrumb">
	<?php foreach ($breadcrumbs as $i => $breadcrumb): ?>
		<?php if ($breadcrumb->path): ?><a class="breadcrumb-link" href='/admin/<?=$breadcrumb->path?>'><?php endif ?>
		<li class="breadcrumb-item <?php if ($i === count($breadcrumbs) - 1): ?>active<?php endif ?>" <?php if ($i === count($breadcrumbs) - 1): ?>aria-current="page"<?php endif ?>><?=$breadcrumb->title?></li>
		<?php if ($breadcrumb->path): ?></a><?php endif ?>
		<?php if ($i !== count($breadcrumbs) - 1): ?>
		<span class="px-1">></span>
		<?php endif ?>
	<?php endforeach ?>
	  <li id="request-progress" class="breadcrum-item htmx-indicator ps-2">
		<div class="spinner-border spinner-border-sm text-dark" role="status">
			<span class="visually-hidden">Loading...</span>
		</div>
	  </li>
  </ol>
</nav>
