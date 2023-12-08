// HTMX
document.body.addEventListener('htmx:beforeSwap', function(evt) {
	// Unauthorized / Session has expired
    if(evt.detail.xhr.status === 401){
		window.location.href = "/admin/sign-out";
    }
	// Permission denied
    if(evt.detail.xhr.status === 403){
        alert("Permission denied");
    }
	// Fatal error
    if(evt.detail.xhr.status === 500){
        alert("Fatal error");
    }
});

