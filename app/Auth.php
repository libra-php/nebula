<?php

namespace App;

use App\Models\User;
use App\Models\Factories\UserFactory;
use Sonata\GoogleAuthenticator\{GoogleQrUrl, GoogleAuthenticator};

class Auth
{
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2I);
    }

    public static function validatePassword(User $user, string $password): bool
    {
        return password_verify($password, $user->password);
    }

    public static function registerUser(
        string $email,
        string $name,
        string $password
    ): User {
        $factory = app()->get(UserFactory::class);
        $user = $factory->create($name, $email, $password);
        return $user;
    }

    public static function forgotPassword(?User $user): bool
    {
        // If we have a user, then set the password reset token
        // Only set the token if the token doesn't exist or it is expired
        if (
            $user &&
            (is_null($user->reset_token) || time() > $user->reset_expires_at)
        ) {
            $token = token();
            // TODO move to config?
            $expires = strtotime("+ 15 minute");
            $user->update([
                "reset_token" => $token,
                "reset_expires_at" => $expires,
            ]);
            $template = latte("auth/mail/forgot-password.latte", [
                "name" => $user->name,
                "link" =>
                    config("app.url") .
                    "/password-reset/{$user->uuid}/{$token}/",
                "project" => config("app.name"),
            ]);
            smtp()->send(
                "Password reset",
                $template,
                to_addresses: [$user->email]
            );
            return true;
        }
        sleep(2);
        return false;
    }

    public static function changePassword(User $user, string $password): bool
    {
        return $user->update([
            "password" => Auth::hashPassword($password),
        ]);
    }

    public static function signIn(User $user)
    {
        session()->set("user", $user->uuid);
        session()->set("two_fa", null);
        $user->update([
            "reset_token" => null,
            "reset_expires_at" => null,
        ]);
        return redirectRoute("dashboard.index");
    }

    public static function twoFactorAuthentication(User $user)
    {
        session()->set("two_fa", $user->uuid);
        return redirectRoute("two-factor-authentication.index");
    }

    public static function twoFactorRegister(User $user)
    {
        session()->set("two_fa", $user->uuid);
        return redirectRoute("two-factor-register.index");
    }

    public static function urlQR(User $user): string
    {
        return GoogleQrUrl::generate(
            $user->email,
            $user->two_fa_secret,
            config("app.name")
        );
    }

    public static function generateTwoFASecret(): string
    {
        $g = new GoogleAuthenticator();
        return $g->generateSecret();
    }

    public static function validateCode(string $secret, string $code): bool
    {
        $g = new GoogleAuthenticator();
        return $g->checkCode($secret, $code);
    }
}
