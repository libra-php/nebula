// Handle checkboxes
if (typeof checkboxes === 'undefined') {
  let checkboxes = document.querySelectorAll(".control-checkbox");
  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', function (e) {
      const target = e.currentTarget;
      const hidden = target.previousSibling;
      const checked = target.checked;
      hidden.value = checked ? 1 : 0;
    })
  });
}
