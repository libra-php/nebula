<?php

namespace App\Controllers\Auth;

use Nebula\Controller\Controller;
use StellarRouter\{Get, Group};

#[Group(prefix: "/admin")]
final class SignOutController extends Controller
{
    #[Get("/sign-out", "sign-out.index", ["push-url=/admin/sign-in"])]
    public function index(): mixed
    {
        session()->destroy();
        return redirectRoute("sign-in.index");
    }
}
