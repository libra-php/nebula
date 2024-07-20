<section id="register-2fa" class="d-flex justify-content-center flex-column align-items-center vh-100">
	<h3>Two Factor Authentication</h3>
    <img height="200" width="200" src="data:image/png;base64, <?php echo $qr; ?> "/>
    <div class="container">
        <p>Please scan this QR code with your Google Authenticator application.</p>
        <p>Once that is complete, enter your 6-digit pin to complete registration.</p>
    </div>
    <?=$form?>
</section>
