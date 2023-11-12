<?php

namespace App;

use App\Models\User;
use App\Models\Factories\UserFactory;
use Nebula\Alerts\Flash;
use Carbon\Carbon;
use Sonata\GoogleAuthenticator\{GoogleQrUrl, GoogleAuthenticator};

class Auth
{
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2I);
    }

    public static function validatePassword(User $user, string $password): bool
    {
        $result = password_verify($password, $user->password);
        self::handleFailedLoginAttempts($result, $user);
        $user->save();
        return $result;
    }

    public static function handleFailedLoginAttempts(bool $result, User $user)
    {
        // If the result is a fail, increment the user's failed attempts
        if (!$result) {
            $user->failed_login_attempts++;
            $user->save();
        }
        // If the user has went over the threshold...
        if ($user->failed_login_attempts >= config("user.max_login_attempts")) {
            // If the user has not previously been locked, then lock
            if (!$user->reset_expires_at) {
                self::lockUser($user);
            }
            // Unlock condition
            $unlock_user = time() >= $user->reset_expires_at;
            if ($unlock_user) {
                // Unlock the poor user
                self::unlockUser($user);
            } else {
                // This account is still locked
                self::handleLockedUser($user);
            }
        }
    }


    public static function lockUser(User $user)
    {
        // Set user lock expires_at (unix time)
        $minutes = intval(config("user.lock_minutes"));
        $expires_at = strtotime("+ {$minutes} minutes");
        $user->reset_expires_at = $expires_at;
        $user->save();
    }

    public static function unlockUser(User $user)
    {
        // Reset lock values
        $user->failed_login_attempts = 0;
        $user->reset_expires_at = null;
        $user->save();
    }

    public static function handleLockedUser(User $user)
    {
        // Redirect to sign in page and set messaage
        $unlocks_at = Carbon::createFromTimestamp($user->reset_expires_at)->diffForHumans();
        Flash::addFlash("warning",  "This account is currently locked.<br>Account will unlock {$unlocks_at}.");
        return redirectRoute("sign-in.index");
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
                "link" => config("app.url") . buildRoute('password-reset.index', $user->uuid, $token),
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

    public static function getUser()
    {
        $uuid = session()->get("user");
        return User::search(["uuid", $uuid]);
    }

    public static function signIn(User $user)
    {
        session()->set("user", $user->uuid);
        session()->set("two_fa", null);
        $user->update([
            "reset_token" => null,
            "reset_expires_at" => null,
        ]);
        return redirectModule("module.index", "home");
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

    public static function validateCode(User $user, string $code): bool
    {
        $g = new GoogleAuthenticator();
        $result = $g->checkCode($user->two_fa_secret, $code);
        self::handleFailedLoginAttempts($result, $user);
        $user->save();
        return $result;
    }
}
