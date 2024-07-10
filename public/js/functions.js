/**
 * Handle form checkboxes
 */
var handleCheck = (e) => {
	const self = e.currentTarget;
	const hidden = self.previousElementSibling;
	hidden.value = self.checked ? 1 : 0;
}

/**
 * Hide sidebar when link is clicked (mobile)
 */
var hideSidebar = () => {
	const menu = document.querySelector("#sidebar").classList.add("d-none");
}

