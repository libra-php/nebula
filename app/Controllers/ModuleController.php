<?php

namespace App\Controllers;

use Nebula\Backend\Module;
use Nebula\Controller\Controller;
use Nebula\Traits\Http\Response;
use StellarRouter\{Get, Post, Delete, Patch, Group};

#[Group(prefix: "/admin/v1", middleware: ["auth", "module"])]
class ModuleController extends Controller
{
    use Response;

    private Module $module;

    public function __construct()
    {
        // Using the route params to determine the module, or 404
        $module_name =
            request()->route->getParameters()["module"] ?? "module_unknown";
        $this->module = $this->getModule($module_name);

        parent::__construct();
    }

    /**
     * Get module from modules path class map
     */
    private function getModule(string $module_name): Module
    {
        $module_name = strtok($module_name, "?");
        $module_map = classMap(config("paths.modules"));
        foreach ($module_map as $class => $_) {
            $module = new $class();
            if ($module->getModuleName() === $module_name) {
                return $module;
            }
        }
        $this->moduleNotFound();
    }

    /**
     * No module found, 404
     */
    private function moduleNotFound(): never
    {
        $module = new Module('error');
        $module->moduleNotFound();
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
    public function create(string $module)
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
    public function edit(string $module, string $id)
    {
        return $this->module->edit($id);
    }

    #[Get("/{module}/{id}/edit/part", "module.edit.part", ["push-url"])]
    public function edit_part(string $module, string $id)
    {
        return $this->module->editPartial($id);
    }

    /**
     * Store module in db
     */
    #[Post("/{module}/store", "module.store", ["api"])]
    public function store(string $module)
    {
        return $this->module->store();
    }

    /**
     * Update a module (using post for files)
     */
    #[Post("/{module}/{id}/update", "module.update", ["api"])]
    public function update(string $module, string $id)
    {
        return $this->module->update($id);
    }

    /**
     * Destroy a module
     */
    #[Delete("/{module}/{id}/destroy", "module.destroy", ["api"])]
    public function destroy(string $module, string $id)
    {
        return $this->module->destroy($id);
    }
}
