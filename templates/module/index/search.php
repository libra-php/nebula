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
			class="btn btn-success">OK</button>
	</div>
</form>
<?php endif ?>
