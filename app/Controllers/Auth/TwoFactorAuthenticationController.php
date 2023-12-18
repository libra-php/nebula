<?php

namespace App\Controllers\Auth;

use App\System\Auth;
use App\Models\User;
use Nebula\Controller\Controller;
use Nebula\Validation\Validate;
use StellarRouter\{Get, Post, Group};

#[Group(prefix: "/admin")]
final class TwoFactorAuthenticationController extends Controller
{
    private User $user;

    public function __construct()
    {
        $uuid = session()->get("two_fa");
        $user = User::search(["uuid", $uuid]);
        if (is_null($user)) {
            redirectRoute("sign-in.index");
        }
        $this->user = $user;
    }

    #[
        Get("/two-factor-authentication", "two-factor-authentication.index", [
            "push-url",
        ])
    ]
    public function index(?string $block = null): string
    {
        return latte("auth/two-factor-authentication.latte", [], $block);
    }

    #[
        Get(
            "/two-factor-authentication/part",
            "two-factor-authentication.part",
            ["push-url"]
        )
    ]
    public function part(): string
    {
        return $this->index("body");
    }

    #[Post("/two-factor-authentication", "two-factor-authentication.post")]
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
                return Auth::signIn($this->user);
            } else {
                Validate::addError("code", "Bad code, please try again");
            }
        }
        return $this->part();
    }
}
