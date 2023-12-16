// HTMX
document.body.addEventListener('htmx:beforeSwap', function(evt) {
	// Unauthorized / Session has expired
    if(evt.detail.xhr.status === 401){
		window.location.href = "/admin/sign-out";
    }
	// Module not found
    if(evt.detail.xhr.status === 404){
		window.location.href = "/admin/home";
    }
	// Permission denied
    if(evt.detail.xhr.status === 403){
		window.location.href = "/admin/home";
    }
	// Fatal error
    if(evt.detail.xhr.status === 500){
		window.location.href = "/admin/home";
    }
});

