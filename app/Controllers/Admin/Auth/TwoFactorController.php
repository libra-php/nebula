<?php

namespace App\Controllers\Admin\Auth;

use App\Models\User;
use Nebula\Framework\Auth\Auth;
use Nebula\Framework\Controller\Controller;
use StellarRouter\{Get, Post};

class TwoFactorController extends Controller
{
    #[Get("/register/two-factor-authentication", "2fa.register", ["Hx-Push-Url=/register/two-factor-authentication"])]
    public function register(): string
    {
        $id = session()->get("2fa_user");
        if (!$id) {
            $this->permissionDenied();
        }
        $user = new User($id);
        $content = template("auth/two-factor-register.php", ["form" => $this->form(), "qr" => Auth::generateQRCode($user)]);

        return $this->render("layout/base.php", ["main" => $content]);
    }

    /**
     * @param array<int,mixed> $data sign-in form data
     */
    private function form(array $data = []): string
    {
        return $this->render("auth/form/two-factor-code.php");
    }

    #[Get("/sign-in/two-factor-authentication", "2fa.sign-in", ["Hx-Push-Url=/sign-in/two-factor-authentication"])]
    public function sign_in(): string
    {
        $content = template("auth/two-factor-sign-in.php");

        return $this->render("layout/base.php", ["main" => $content]);
    }

    #[Post("/two-factor-authentication", "2fa.check-code")]
    public function check_code()
    {
        dd('wip');
    }
}
