<?php if($show): ?>
<div id="filter-search">
<input hx-get=""
	hx-indicator="#request-progress"
	hx-swap="outerHTML"
	hx-target="#filters-table"
	hx-select="#filters-table"
	hx-trigger="input changed delay:500ms, search"
	name="term" value="<?=$term?>"
	type="search"
	class="form-control form-control-sm"
	placeholder="Search" />
</div>
<?php endif ?>
