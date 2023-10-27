function toggleProfiler(e)
{
  const div = e.currentTarget.nextElementSibling;
  div.classList.toggle("hidden");
}

if (typeof sidebar_links === 'undefined') {
  // Sidebar active toggle
  const sidebar_links = document.querySelectorAll('#sidebar .nav-pills li.nav-item');
  sidebar_links.forEach((link) => {
    link.addEventListener('click', function(e) {
      // Remove active from all sidebar links
      sidebar_links.forEach((l) => l.classList.remove("active"));
      const target = e.currentTarget;
      // Add active to the clicked element
      target.classList.add('active');
    });
  });
}
