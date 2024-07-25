<?php if($show): ?>
<form hx-get=""
	hx-swap="outerHTML"
	hx-target="#content"
	hx-select="#content"
	hx-indicator="#request-progress">
	<div id="filter-search" class="mb-2 w-100 input-group">
		<input name="term"
			value="<?=$term?>"
			type="search"
			class="form-control form-control-sm"
			placeholder="Search" />
		 <button type="submit"
			class="btn btn-outline-secondary"
			hx-sync="this:abort">OK</button>
	</div>
</form>
<?php endif ?>
