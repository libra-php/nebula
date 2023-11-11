<?php

namespace App\Models\Factories;

use Nebula\Model\Factory;
use App\Models\User;
use App\Auth;

class UserFactory extends Factory
{
    protected string $model = User::class;

    /**
     * Create a new user model
     * @param string $name
     * @param string $email
     * @param string $password
     * @return User|null
     */
    public function create(
        string $name,
        string $email,
        string $password,
        int $user_type = 2
    ): ?User {
        $user = app()->get($this->model);
        $user->name = $name;
        $user->email = $email;
        $user->password = Auth::hashPassword($password);
        $user->two_fa_secret = Auth::generateTwoFASecret();
        $user->failed_login_attempts = 0;
        $user->user_type = $user_type;
        $id = $user->save();
        return User::find($id);
    }

    /**
     * @return array<string,string>
     */
    public function default(): array
    {
        return [
            "name" => "Administrator",
            "email" => "admin@nebula.dev",
            "password" => Auth::hashPassword("admin"),
        ];
    }

    /**
     * @return array<string,string>
     */
    public function mock(): array
    {
        $faker = \Faker\Factory::create();
        return [
            "name" => $faker->name,
            "email" => $faker->email,
            "password" => $faker->password,
        ];
    }
}
