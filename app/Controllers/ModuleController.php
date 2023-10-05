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
        $module_name = request()->route->getParameters()['module'] ?? 'module_unknown';
        $this->module = $this->getModule($module_name);
        
        parent::__construct();
    }

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

    private function moduleNotFound(): never
    {
        echo $this->response(404, "Module not found")->send();
        die;
    }

    #[Get("/{module}", "module.index", ["push-url"])]
    public function index(string $module)
    {
        return $this->module->table();
    }

    #[Post("/{module}", "module.post")]
    public function post(string $module)
    {
    }

    #[Get("/{module}/create", "module.create")]
    public function create(string $module)
    {
    }

    #[Get("/{module}/{id}/edit", "module.edit", ["push-url"])]
    public function edit(string $module, string $id)
    {
        return $this->module->form();
    }

    #[Post("/{module}/store", "module.store")]
    public function store(string $module)
    {
    }

    #[Patch("/{module}/{id}/update", "module.save")]
    public function update(string $module, string $id)
    {
    }

    #[Delete("/{module}/{id}/destroy", "module.destroy")]
    public function destroy(string $module, string $id)
    {
    }
}
