<?php

namespace App\Controllers\Module;

use App\Models\Module as Model;
use Nebula\Alerts\Flash;
use Nebula\Admin\Module;
use Nebula\Controller\Controller;
use Nebula\Traits\Http\Response;
use StellarRouter\{Get, Post, Delete, Group};

#[Group(prefix: "/admin", middleware: ["auth", "module"])]
class ModuleController extends Controller
{
    use Response;

    private Module $module;

    public function __construct()
    {
        $this->checkMaintenanceMode();
        $this->loadModule();

        parent::__construct();
    }

    private function checkMaintenanceMode()
    {
        // Check if admin is in maintenance mode
        if (config("admin.maintenance_mode")) {
            Flash::addFlash(
                "warning",
                "Maintenance mode. Please check back soon."
            );
            redirectRoute("sign-in.index");
        }
    }

    private function loadModule()
    {
        // Using the route params to determine the module, or 404
        $params = request()->route->getParameters();
        if (!empty($params)) {
            $module_name = $params["module"];
            $module = $this->getModule($module_name);
            if (!is_null($module)) {
                $this->module = $module;
            } else {
                $this->fatalError();
            }
        }
    }

    /**
     * Get module from modules path class map
     */
    private function getModule(string $module_name): ?Module
    {
        $module_name = strtok($module_name, "?");
        $module = Model::search(["module_name", $module_name]);
        // Check if module exists
        if (is_null($module)) {
            // Redirect to some random place that doesn't exist
            redirect("/module-not-found");
        }
        // Check if user has permission
        if (user()->user_type > $module->user_type) {
            $this->permissionDenied();
        }
        $class = $module->class_name;
        try {
            $module_class = new $class();
            $module_class->init($module);
        } catch (\Throwable $th) {
            if (config("app.debug")) {
                error_log($th->getMessage());
            }
            return null;
        }
        return $module_class;
    }

    #[Get("/", "module.admin")]
    public function admin()
    {
        redirectHome();
    }

    #[Post("/{module}", "module.index.post")]
    #[Get("/{module}", "module.index", ["push-url"])]
    public function index(string $module): string
    {
        return $this->module->index();
    }

    #[Get("/{module}/part", "module.index.part", ["push-url"])]
    public function index_part(string $module): string
    {
        return $this->module->indexPartial();
    }

    /**
     * Show create module form
     */
    #[Post("/{module}/create", "module.create.post")]
    #[Get("/{module}/create", "module.create", ["push-url"])]
    public function create(string $module): string
    {
        return $this->module->create();
    }

    #[Get("/{module}/create/part", "module.create.part", ["push-url"])]
    public function create_part(string $module)
    {
        return $this->module->createPartial();
    }

    /**
     * Show module edit form
     */
    #[Post("/{module}/{id}/edit", "module.edit.post")]
    #[Get("/{module}/{id}/edit", "module.edit", ["push-url"])]
    public function edit(string $module, string $id): string
    {
        return $this->module->edit($id);
    }

    #[Get("/{module}/{id}/edit/part", "module.edit.part", ["push-url"])]
    public function edit_part(string $module, string $id): string
    {
        return $this->module->editPartial($id);
    }

    /**
     * Store module in db
     */
    #[Post("/{module}/store", "module.store", ["api"])]
    public function store(string $module): string
    {
        return $this->module->store();
    }

    /**
     * Update a module (using post for files)
     */
    #[Post("/{module}/{id}/update", "module.update", ["api"])]
    public function update(string $module, string $id): string
    {
        return $this->module->update($id);
    }

    /**
     * Destroy a module
     */
    #[Delete("/{module}/{id}/destroy", "module.destroy", ["api"])]
    public function destroy(string $module, string $id): string
    {
        return $this->module->destroy($id);
    }

    /**
     * Module could not be found
     */
    #[Get("/module/not-found", "module.not-found", ["auth"])]
    public function moduleNotFound(): void
    {
        Flash::addFlash("warning", "Module not found");
        $response = $this->response(404, "Module not found");
        echo $response->send();
        exit();
    }

    /**
     * User does not have permission
     */
    #[Get("/module/permission-denied", "module.permission-denied", ["auth"])]
    public function permissionDenied(): void
    {
        Flash::addFlash("warning", "Permission denied");
        $response = $this->response(403, "Permission denied");
        echo $response->send();
        exit();
    }

    /**
     * A fatal server error occurred
     */
    #[Get("/module/fatal-error", "module.fatal-error", ["auth"])]
    public function fatalError(): void
    {
        Flash::addFlash("warning", "Fatal error");
        $response = $this->response(500, "Fatal error");
        echo $response->send();
        exit();
    }
}
