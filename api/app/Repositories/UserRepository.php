<?php


namespace App\Repositories;


use App\Model\User;
use Illuminate\Pagination\AbstractPaginator;
use Laravel\Passport\Token;

interface UserRepository
{
    /**
     * Create a new user
     * @param string $firstName Users first name
     * @param string $lastName Users last name
     * @param string $email Users email
     * @param string $password Users password
     * @param string $confirmationCode Confirmation code to be sent by email
     * @return User GetUserResource that was created
     */
    public function createUser(
        string $firstName,
        string $lastName,
        string $email, string $password,
        string $confirmationCode): User;

    public function deleteUserById(int $userId): void;

    public function revokeAccessToken(Token $token): void;

    public function revokeRefreshToken(Token $token): void;

    public function findByEmail(string $userEmail): ?User;

    public function findById(int $userId): ?User;

    public function getPaginatedUsersCriteria(
        string $queryString,
        string $orderColumn,
        string $orderDirection,
        int $itemsPerPage,
        int $currentPage
    ): AbstractPaginator;

    public function createFacebookUser(string $facebookId, string $firstName, string $lastName, string $email): User;

    public function addFacebookToUser(User $user, string $facebookId): User;

    public function findByConfirmationCode(string $confirmationCode): User;

    public function confirmAccount(User $user): void;

    public function addConfirmationCode(User $user, string $confirmationCode): void;
}