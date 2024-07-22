<form method="POST" id="form-sign-in" hx-post="<?=route('sign-in.post')?>" hx-swap="outerHTML">
	<h3>Sign In</h3>
	<?= $csrf() ?>
	<div>
		<label>Email</label><br>
		<input class="form-control" name="email" type="email" value="<?= $escape('email') ?>" required />
		<?= $request_errors('email') ?>
	</div>
	<div>
		<label>Password</label><br>
		<input class="form-control" name="password" type="password" value="" required />
		<?= $request_errors('password') ?>
	</div>
	<div class="my-2">
		<input type="checkbox" name="remember_me" value="1" /> <label class="ps-1">Remember me</label>
	</div>
	<div>
		<p><a hx-boost="true" href="/register" hx-select="main" hx-target="main">Don't have an account?</a></p>
	</div>
	<div>
		<button class="btn btn-primary">Submit</button>
	</div>
</form>
