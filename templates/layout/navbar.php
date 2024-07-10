<nav id="navbar" class="navbar navbar-light text-dark sticky-top">
    <div class="d-flex align-items-center">
        <span class="navbar-brand ps-2 m-0"><?= $app_name ?></span>
        <section id="request-progress" class="htmx-indicator ps-2">
            <div class="spinner-border spinner-border-sm text-info" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </section>
    </div>
    <div class="ms-auto pe-2">
        <span id="toggle-sidebar" class="d-block d-sm-none text-light" data-feather="menu"></span>
    </div>
</nav>
