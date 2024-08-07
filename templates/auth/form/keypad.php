<form method="POST" id="form-register-2fa" hx-post="<?=route('2fa.code')?>" hx-swap="outerHTML">
	<?= $csrf() ?>
    <input type="tel" placeholder="Enter your code" name="code" id="code" class="form-control" maxlength="6" minlength="6" required />
		<?= $request_errors('code') ?>
    <div class="d-flex flex-wrap justify-content-center mt-2" id="keypad">
      <button class="btn btn-light" type="button" onClick="updateInput(event)" value="1">1</button>
      <button class="btn btn-light" type="button" onClick="updateInput(event)" value="2">2</button>
      <button class="btn btn-light" type="button" onClick="updateInput(event)" value="3">3</button>
      <button class="btn btn-light" type="button" onClick="updateInput(event)" value="4">4</button>
      <button class="btn btn-light" type="button" onClick="updateInput(event)" value="5">5</button>
      <button class="btn btn-light" type="button" onClick="updateInput(event)" value="6">6</button>
      <button class="btn btn-light" type="button" onClick="updateInput(event)" value="7">7</button>
      <button class="btn btn-light" type="button" onClick="updateInput(event)" value="8">8</button>
      <button class="btn btn-light" type="button" onClick="updateInput(event)" value="9">9</button>
      <button class="btn btn-light" type="button" onClick="deleteChar(event)">⌫</button>
      <button class="btn btn-light" type="button" onClick="updateInput(event)" value="0">0</button>
      <button class="btn btn-light" type="submit">⏎</button>
    </div>
</form>
<script src="/js/keypad.js" defer></script>
