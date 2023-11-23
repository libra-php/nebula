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
  plugins: 'powerpaste casechange searchreplace autolink directionality visualblocks visualchars image link media mediaembed codesample table charmap pagebreak nonbreaking anchor tableofcontents insertdatetime advlist lists checklist wordcount tinymcespellchecker editimage help formatpainter permanentpen charmap linkchecker emoticons advtable export autosave advcode code fullscreen',
  toolbar: 'undo redo print spellcheckdialog formatpainter | blocks fontfamily fontsize | bold italic underline forecolor backcolor | link image | alignleft aligncenter alignright alignjustify | code',
  advcode_inline: true,
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
