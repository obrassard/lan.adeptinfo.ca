<?php


namespace App\Repositories\Implementation;


use App\Model\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UserRepositoryImpl implements UserRepository
{
    public function createUser(string $firstName, string $lastName, string $email, string $password): User
    {
        $user = new User();
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->save();

        return $user;
    }
}