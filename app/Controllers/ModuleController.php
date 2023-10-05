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
        $module_name = request()->route->getParameters()['module'] ?? 'module_unknown';
        $this->module = $this->getModule($module_name);

        parent::__construct();
    }

    /**
     * Get module from modules path class map
     */
    private function getModule(string $module_name): Module
    {
        $module_map = classMap(config("paths.modules"));
        foreach ($module_map as $class => $_) {
            $module = new $class;
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
        echo $this->response(404, latte("backend/not-found.latte"))->send();
        die;
    }

    /**
     * Table view
     */
    #[Get("/{module}", "module.index", ["push-url"])]
    public function index(string $module)
    {
        return $this->module->index();
    }

    #[Get("/{module}/part", "module.index.part")]
    public function index_part(string $module)
    {
        return $this->module->indexPartial();
    }

    /**
     * Show create module form
     */
    #[Get("/{module}/create", "module.create")]
    public function create(string $module)
    {
        return $this->module->create();
    }

    /**
     * Show module edit form
     */
    #[Get("/{module}/{id}/edit", "module.edit", ["push-url"])]
    public function edit(string $module, string $id)
    {
        return $this->module->edit($id);
    }

    /**
     * Store module in db
     */
    #[Post("/{module}/store", "module.store")]
    public function store(string $module)
    {
    }

    /**
     * Update a module
     */
    #[Patch("/{module}/{id}/update", "module.save")]
    public function update(string $module, string $id)
    {
    }

    /**
     * Destroy a module
     */
    #[Delete("/{module}/{id}/destroy", "module.destroy")]
    public function destroy(string $module, string $id)
    {
    }
}
