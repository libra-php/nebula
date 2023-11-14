<?php

namespace App\Controllers;

use App\Models\Module as NebulaModule;
use Nebula\Alerts\Flash;
use Nebula\Backend\Module;
use Nebula\Controller\Controller;
use Nebula\Traits\Http\Response;
use StellarRouter\{Get, Post, Delete, Group};

#[Group(prefix: "/admin/v1", middleware: ["auth", "module"])]
class ModuleController extends Controller
{
    use Response;

    private Module $module;

    public function __construct()
    {
        // Check if backend is in maintenance mode
        if (config("backend.maintenance_mode")) {
            Flash::addFlash("warning", "Maintenance mode. Please check back soon.");
            redirectRoute("sign-in.index");
        }
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

        parent::__construct();
    }

    /**
     * Get module from modules path class map
     */
    private function getModule(string $module_name): ?Module
    {
        $module_name = strtok($module_name, "?");
        $module = NebulaModule::search(["module_name", $module_name]);
        // Check if module exists
        if (is_null($module)) {
            $this->moduleNotFound();
        }
        // Check if user has permission
        if (user()->user_type > $module->user_type) {
            $this->permissionDenied();
        }
        $class = $module->class_name;
        try {
            $module_class = new $class();
        } catch (\Throwable $th) {
            if (config('app.debug')) {
                Flash::addFlash("info", $th->getMessage());
            }
            return null;
        }
        return $module_class;
    }

    #[Get("/module/permission-denied", "module.permission-denied")]
    public function permissionDenied(): void
    {
        $module = new Module("error");
        $module->fatalError();
    }

    #[Get("/module/not-found", "module.not-found")]
    public function moduleNotFound(): void
    {
        $module = new Module("error");
        $module->moduleNotFound();
    }

    #[Get("/module/fatal-error", "module.fatal-error")]
    public function fatalError(): void
    {
        $module = new Module("error");
        $module->fatalError(true);
    }

    /**
     * Table view
     */
    #[Post("/{module}", "module.index.post")]
    #[Get("/{module}", "module.index", ["push-url"])]
    public function index(string $module)
    {
        return $this->module->index();
    }

    #[Get("/{module}/part", "module.index.part", ["push-url"])]
    public function index_part(string $module)
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
    public function create_part(string $module): string
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
}
