<?php

namespace App\Controllers\Admin\Auth;

use App\Models\User;
use Nebula\Framework\Auth\Auth;
use Nebula\Framework\Controller\Controller;
use StellarRouter\{Get, Post};

class TwoFactorController extends Controller
{
    private User $user;

    protected function bootstrap(): void
    {
        $id = session()->get("2fa_user");
        if (!$id) {
            $this->permissionDenied();
        }
        $this->user = new User($id);
    }

    #[Get("/register/two-factor-authentication", "2fa.register", ["Hx-Push-Url=/register/two-factor-authentication"])]
    public function register(): string
    {
        $content = template("auth/two-factor-register.php", [
            "form" => $this->form(),
            "qr" => Auth::generateQRCode($this->user)
        ]);

        return $this->render("layout/base.php", ["main" => $content]);
    }

    /**
     * @param array<int,mixed> $data sign-in form data
     */
    private function form(array $data = []): string
    {
        return $this->render("auth/form/keypad.php");
    }

    #[Get("/sign-in/two-factor-authentication", "2fa.sign-in", ["Hx-Push-Url=/sign-in/two-factor-authentication"])]
    public function sign_in(): string
    {
        $content = template("auth/two-factor-sign-in.php", [
            "form" => $this->form(),
            "qr" => Auth::generateQRCode($this->user)
        ]);

        return $this->render("layout/base.php", ["main" => $content]);
    }

    #[Post("/two-factor-authentication", "2fa.code")]
    public function code()
    {
        $request = $this->validateRequest([
            "code" => ["required", "minlength|6"],
        ]);
        if ($request) {
            if (Auth::verifyTwoFactor($this->user, $request["code"])) {
                Auth::signIn($this->user);
            } else {
                $this->addRequestError("code", "Bad security code");
            }
        }
        return $this->form();
    }
}
