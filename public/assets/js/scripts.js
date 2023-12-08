document.body.addEventListener('htmx:beforeSwap', function(evt) { (1)
	// Unauthorized / Session has expired
    if(evt.detail.xhr.status === 401){
		window.location.href = "/admin/sign-out";
    }
});

