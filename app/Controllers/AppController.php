<?php

namespace App\Controllers;

use Nebula\Controller\Controller;
use StellarRouter\{Get, Group};

#[Group(middleware: ["auth"])]
class AppController extends Controller
{
    #[Get("/dashboard", "dashboard.index", ["push-url"])]
    public function index(): string
    {
        return latte("dashboard/index.latte");
    }

    // Svelte app
    // #[Get("/app", "app.index", ["push-url"])]
    // public function index(): string
    // {
    //     return latte("app/index.latte");
    // }
}
