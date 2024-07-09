<nav id="navbar" class="navbar navbar-light text-dark sticky-top">
    <span class="navbar-brand ps-2 m-0"><?= $app_name ?></span>
    <span id="request-progress" class="ps-2 htmx-indicator">
        <div class="spinner-border spinner-border-sm text-info" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </span>
    <div class="ms-auto pe-2">
        <span id="toggle-sidebar" class="d-block d-sm-none text-light" data-feather="menu"></span>
    </div>
</nav>
