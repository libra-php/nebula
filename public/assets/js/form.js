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

// WYSIWYG editor
tinymce.init({
  autosave_interval: '3s',
  height: "800px",
  setup: function(editor) {
    const update = () => {
      const id = this.id
      const el = document.getElementById(id);
      var content = tinymce.get(id).getContent();
      el.innerText = content;
    }
    editor.on('input', update);
    editor.on('change', update);
    editor.on('blur', update);
  },
  selector: '.control-editor'
});
