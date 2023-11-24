<?php

namespace App\Controllers\Auth;

use App\Auth;
use App\Models\User;
use Nebula\Controller\Controller;
use Nebula\Validation\Validate;
use StellarRouter\{Get, Post, Group};

#[Group(prefix: "/admin")]
final class TwoFactorRegisterController extends Controller
{
    private User $user;

    public function __construct()
    {
        $register = session()->get("register_two_fa");
        $uuid = session()->get("two_fa");
        $user = User::search(["uuid", $uuid]);
        if (is_null($register) || is_null($user)) {
            redirectRoute("sign-in.index");
        }
        $this->user = $user;
    }

    #[
        Get(
            "/two-factor-authentication/register",
            "two-factor-register.index",
            ["push-url"]
        )
    ]
    public function index(): string
    {
        $url = Auth::urlQR($this->user);
        return latte("auth/two-factor-register.latte", ["url" => $url]);
    }

    #[
        Get(
            "/two-factor-authentication/register/part",
            "two-factor-register.part",
            ["push-url"]
        )
    ]
    public function part(): string
    {
        $url = Auth::urlQR($this->user);
        return latte(
            "auth/two-factor-register.latte",
            ["url" => $url],
            block: "body"
        );
    }

    #[Post("/two-factor-authentication/register", "two-factor-register.post")]
    public function post(): string
    {
        if (
            $this->validate([
                "code" => [
                    "required",
                    "numeric",
                    "min_length=6",
                    "max_length=6",
                ],
            ])
        ) {
            if (Auth::validateCode($this->user, request()->code)) {
                session()->set("register_two_fa", false);
                return Auth::signIn($this->user);
            } else {
                Validate::addError("code", "Bad code, please try again");
            }
        }
        return $this->part();
    }
}
