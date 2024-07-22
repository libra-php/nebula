<form method="POST" id="form-register" hx-post="<?=route('register.post')?>" hx-swap="outerHTML" hx-target="#form-register" hx-select="#form-register">
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
		<button class="btn btn-primary" type="submit">Submit</button>
	</div>
</form>
