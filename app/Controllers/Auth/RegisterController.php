<?php

namespace App\Controllers\Auth;

use Nebula\Controller\Controller;
use Nebula\Validation\Validate;
use App\System\Auth;
use StellarRouter\{Get, Post, Group};

#[Group(prefix: "/admin")]
final class RegisterController extends Controller
{
    public function __construct()
    {
        // Disable registration if the config is set to false
        if (!config("auth.register_enabled")) {
            redirectRoute("sign-in.part");
        }

        if (user()) {
            redirectHome();
        }
    }

    #[Get("/register", "register.index")]
    public function index(?string $block = null): string
    {
        return latte(
            "auth/register.latte",
            [
                "two_fa_enabled" => config("auth.two_fa_enabled"),
                "name" => request()->get("name"),
            ],
            $block
        );
    }

    #[Get("/register/part", "register.part", ["push-url"])]
    public function part(): string
    {
        return $this->index("body");
    }

    #[Post("/register", "register.post", ["rate_limit"])]
    public function post(): string
    {
        // Provide a custom message for the unique rule
        Validate::$messages["unique"] =
            "An account already exists for this email address";
        if (
            $this->validate([
                "name" => ["required"],
                "email" => ["required", "unique=users", "email"],
                "password" => [
                    "required",
                    "min_length=8",
                    "uppercase=1",
                    "lowercase=1",
                    "symbol=1",
                ],
                // Note: you can change the label so that it
                // doesn't say Password_match in the UI
                "password_match" => ["Password" => ["required", "match"]],
            ])
        ) {
            $user = Auth::registerUser(
                request()->email,
                request()->name,
                request()->password
            );
            if ($user) {
                if (config("auth.two_fa_enabled")) {
                    session()->set("register_two_fa", true);
                    return Auth::twoFactorRegister($user);
                } else {
                    return Auth::signIn($user);
                }
            }
        }
        return $this->part();
    }
}
