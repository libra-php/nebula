<form method="POST" id="form-register-2fa" hx-post="<?=route('2fa.check-code')?>" hx-swap="outerHTML">
	<?= $csrf() ?>
    <input type="tel" name="code" id="code" class="form-control" />
    <div class="d-flex flex-wrap justify-content-center mt-2" id="keypad">
      <button class="btn btn-light" type="button" value="1">1</button>
      <button class="btn btn-light" type="button" value="2">2</button>
      <button class="btn btn-light" type="button" value="3">3</button>
      <button class="btn btn-light" type="button" value="4">4</button>
      <button class="btn btn-light" type="button" value="5">5</button>
      <button class="btn btn-light" type="button" value="6">6</button>
      <button class="btn btn-light" type="button" value="7">7</button>
      <button class="btn btn-light" type="button" value="8">8</button>
      <button class="btn btn-light" type="button" value="9">9</button>
      <button class="btn btn-light" type="button" value="bs">⌫</button>
      <button class="btn btn-light" type="button" value="0">0</button>
      <button class="btn btn-light" type="button" value="ent">⏎</button>
    </div>
</form>
