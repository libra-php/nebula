function toggleProfiler(e) {
  const div = e.currentTarget.nextElementSibling;
  div.classList.toggle("hidden");
}

function handleSidebarActive(e) {
  const el = e.currentTarget;
  const sidebar_links = document.querySelectorAll('#sidebar .nav-pills li.nav-item');
  sidebar_links.forEach((l) => {
    if (l.dataset.module === el.dataset.module) {
      l.classList.add("active")
    } else {
      l.classList.remove("active")
    }
  });
}

function disablePaginationLink(e) {
  e.preventDefault();
  e.currentTarget.parentElement.classList.add("disabled");
}
