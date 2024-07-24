<?php

namespace Nebula\Framework\Auth;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Exception;

class Auth
{
    /**
     * Return the current authenticated user or null
     * @return ?User
     */
    public static function user(): ?User
    {
        try {
            $id = session()->get("user_id");
            $uuid = $_COOKIE["user_uuid"] ?? null;
            if ($id) {
                return new User($id);
            } elseif ($uuid) {
                $user = User::findByAttribute("uuid", $uuid);
                return new User($user->id);
            }
        } catch (Exception $ex) {
            error_log("Invalid auth user... Destroying the current session.");
            self::destroy();
            self::redirectSignIn();
        }
        return null;
    }

    /**
     * Redirect to sign-in route
     */
    public static function redirectSignIn(): void
    {
        $route = route("sign-in.index");
        header("HX-Location: $route");
        header("Location: $route");
        exit();
    }

    /**
     * When the user signs in, redirect to sign-in route
     * HTMX redirect
     */
    public static function redirectAdmin($location = true): void
    {
        $route = config("security.sign_in_route");
        header("HX-Location: $route");
        if ($location) header("Location: $route");
        exit();
    }

    /**
     * Redirect register 2FA
     */
    public static function register2FA(User $user): void
    {
        session()->set("2fa_user", $user->id);
        $route = route("2fa.register");
        header("HX-Location: $route");
        header("Location: $route");
        exit();
    }

    /**
     * Redirect sign-in 2FA
     */
    public static function signIn2FA(User $user): void
    {
        session()->set("2fa_user", $user->id);
        $route = route("2fa.sign-in");
        header("HX-Location: $route");
        header("Location: $route");
        exit();
    }

    /**
     * Sign in a user and redirect to sign in route
     * @param User $user
     */
    public static function signIn(User $user): void
    {
        // We shouldn't need to audit a user sign in
        // the session log is good enough
        session()->set("user_id", $user->id);
        // Maybe there is a cookie?
        $remember_me = session()->get("remember_me");
        if ($remember_me) {
            $future_time = time() + 86400 * 30;
            setcookie("user_uuid", $user->uuid, $future_time, "/");
        }
        $user->login_at = date("Y-m-d H:i:s");
        $user->save();
        self::redirectAdmin(false);
    }

    /**
     * Sign out currently authenticated user
     */
    public static function signOut(): void
    {
        self::destroy();
        self::redirectSignIn();
    }

    public static function destroy(): void
    {
        session()->destroy();
        unset($_COOKIE["user_uuid"]);
        setcookie("user_uuid", "", -1, "/");
    }

    /**
     * Return a hashed password using password_hash
     * @param string $password
     * @return string|bool|null
     */
    public static function hashPassword(string $password): string|bool|null
    {
        return password_hash($password, PASSWORD_ARGON2I);
    }

    public static function generateSecretKey(): string
    {
        $google2fa = new Google2FA();
        return $google2fa->generateSecretKey();
    }

    private static function getQRCodeUrl(User $user): string
    {
        $google2fa = new Google2FA();
        return $google2fa->getQRCodeUrl(
            config("application.name"),
            $user->email,
            $user->secret_key
        );
    }

    public static function generateQRCode(User $user): string
    {
        $url = self::getQRCodeUrl($user);
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(400),
                new ImagickImageBackEnd()
            )
        );
        return base64_encode($writer->writeString($url));
    }

    public static function verifyTwoFactor(User $user, string $code): bool
    {
        $google2fa = new Google2FA();
        return $user->secret_key && (bool)$google2fa->verifyKey($user->secret_key, $code);
    }

    /**
     * Authenticate user with email / password combination
     * @param array $data contains email and password credentials from the web form
     * @return User|false based on correct credentials
     */
    public static function userAuth(array $data): User|false
    {
        $user = User::findByAttribute("email", $data["email"]);
        if ($user) {
            $password_valid = password_verify(
                $data["password"],
                $user->password
            );
            if ($password_valid) {
                return $user;
            }
        }
        return false;
    }

    /**
     * Create a new admin user via registration
     * Password is hashed
     * 2FA secret key is generated
     * @param array $data
     * @return User
     */
    public static function registerUser(array $data): User
    {
        $data["password"] = self::hashPassword($data["password"]);
        $data["secret_key"] = self::generateSecretKey();
        $user = User::new($data);
        if ($user) {
            foreach ($data as $column => $value) {
                audit("users", $user->id, $column, $user, null, $value, "REGISTER");
            }
        }
        return $user;
    }
}
