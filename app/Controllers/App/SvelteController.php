<?php

namespace App\Controllers\App;

use Nebula\Controller\Controller;
use StellarRouter\{Get, Group};

#[Group(middleware: ["auth"])]
class SvelteController extends Controller
{
	// Change App\Config\Auth.php sign_in_route to "app.home"
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
