<form method="POST" id="form-register" hx-post="<?=route('register.post')?>" hx-swap="outerHTML">
	<h3>Register</h3>
	<?= $csrf() ?>
	<div id="email-input">
		<label>Email</label><br>
		<input hx-trigger="keyup changed delay:1s" hx-post="<?=route('register.post')?>" hx-select="#email-input" hx-target="#email-input" class="form-control" name="email" type="email" value="<?= $escape('email') ?>" />
		<?= $request_errors("email") ?>
	</div>
	<div id="name-input">
		<label>Name</label><br>
		<input hx-trigger="keyup changed delay:1s" hx-post="<?=route('register.post')?>" hx-select="#name-input" hx-target="#name-input" class="form-control" name="name" type="text" value="<?= $escape('name') ?>" />
		<?= $request_errors("name") ?>
	</div>
	<div id="password-input">
		<label>Password</label><br>
		<input class="form-control" name="password" type="password" value="" required />
		<?= $request_errors("password") ?>
	</div>
	<div id="password-match-input">
		<label>Password (again)</label><br>
		<input class="form-control" name="password_match" type="password" value="" required />
		<?= $request_errors("password_match") ?>
	</div>
	<div class="mt-2">
		<p><a hx-boost="true" href="/sign-in" hx-select="main" hx-target="main">Already have an account?</a></p>
	</div>
	<div>
		<button class="btn btn-success w-100" type="submit">Create</button>
	</div>
	<div class="mt-2 d-flex align-items-center">
		<img class="rounded me-1" src="/img/nebula.jpeg" height="16px" width="16px" alt="nebula" />
		<img class="rounded me-1" src="/img/lock.png" height="16px" width="16px" alt="nebula" />
		<span class="me-1">256-Bit SSL Encryption (AES-256)</span>
	</div>
	<div class="text-center"><small>&copy; <?=date("Y")?> All rights reserved</small></div>
</form>
