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
		<button class="btn btn-primary w-100">Enter</button>
	</div>
	<div class="mt-2 d-flex align-items-center">
		<img class="rounded me-1" src="/img/nebula.jpeg" height="16px" width="16px" alt="nebula" />
		<img class="rounded me-1" src="/img/lock.png" height="16px" width="16px" alt="nebula" />
		<span class="me-1">256-Bit SSL Encryption (AES-256)</span>
	</div>
	<div class="text-center"><small>&copy; <?=date("Y")?> All rights reserved</small></div>
</form>
