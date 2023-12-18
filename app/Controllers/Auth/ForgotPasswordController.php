<?php

namespace App\Controllers\Auth;

use App\System\Auth;
use App\Models\User;
use StellarRouter\{Get, Post, Group};
use Nebula\Controller\Controller;

#[Group(prefix: "/admin")]
final class ForgotPasswordController extends Controller
{
    public function __construct()
    {
        if (user()) {
            redirectHome();
        }
    }

    #[Get("/forgot-password", "forgot-password.index")]
    public function index(
        bool $show_success_message = false,
        ?string $block = null
    ): string {
        return latte(
            "auth/forgot-password.latte",
            [
                "show_success_message" => $show_success_message,
            ],
            $block
        );
    }

    #[Get("/forgot-password/part", "forgot-password.part", ["push-url"])]
    public function part(bool $show_success_message = false): string
    {
        return $this->index($show_success_message, "body");
    }

    #[Post("/forgot-password", "forgot-password.post")]
    public function post(): string
    {
        if (
            $this->validate([
                "email" => ["required", "email"],
            ])
        ) {
            $user = User::search(["email", request()->email]);
            Auth::forgotPassword($user);
            // Always display a success message
            return $this->part(true);
        }
        return $this->part();
    }
}
