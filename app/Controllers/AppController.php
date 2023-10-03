<?php

namespace App\Controllers;

use Nebula\Controller\Controller;
use StellarRouter\{Get, Group};

#[Group(middleware: ["auth"])]
class AppController extends Controller
{
    #[Get("/home", "app.home", ["push-url"])]
    public function index(): string
    {
        return latte("dashboard/index.latte");
    }

    //Svelte app
    // #[Get("/home", "app.home", ["push-url"])]
    // public function home(): string
    // {
    //     return latte("app/index.latte");
    // }
    //
    // #[Get("/about", "app.about", ["push-url"])]
    // public function about(): string
    // {
    //     return latte("app/index.latte");
    // }
}
