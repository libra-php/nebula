function toggleProfiler(e) {
  const div = e.currentTarget.nextElementSibling;
  div.classList.toggle("hidden");
}

function disablePaginationLink(e) {
  e.preventDefault();
  e.currentTarget.parentElement.classList.add("disabled");
}
