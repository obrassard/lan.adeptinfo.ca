<?php


namespace App\Services;


use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface UserService
{
    public function signUpUser(Request $request): User;

    public function deleteUser(): void;

    public function logOut(): void;

    public function getUsers(Request $request): Collection;
}