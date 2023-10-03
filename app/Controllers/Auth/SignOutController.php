<?php

namespace App\Controllers\Auth;

use Nebula\Controller\Controller;
use StellarRouter\Get;

final class SignOutController extends Controller
{
    #[Get("/sign-out", "sign-out.index", ["push-url=/sign-in"])]
    public function index(): mixed
    {
        session()->destroy();
        return redirectRoute("sign-in.index");
    }
}
