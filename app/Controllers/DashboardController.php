<?php

namespace App\Controllers;

use Nebula\Controller\Controller;
use StellarRouter\{Get, Group};

#[Group(middleware: ["auth"])]
class DashboardController extends Controller
{
    #[Get("/dashboard", "dashboard.index", ["push-url"])]
    public function index(): string
    {
        return latte("dashboard/index.latte");
    }

    #[Get("/benchmark", "dashboard.benchmark", ["quick-exit"])]
    public function benchmark(): string
    {
        return "hello, world";
    }
}
