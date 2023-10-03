<?php

namespace App\Controllers;

use Nebula\Controller\Controller;
use StellarRouter\{Get, Group};

#[Group(middleware: ["auth"])]
class AppController extends Controller
{
    #[Get("/app", "app.index", ["push-url"])]
    public function index(): string
    {
        return latte("app/index.latte");
    }
}
