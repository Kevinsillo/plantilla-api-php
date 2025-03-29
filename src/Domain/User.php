<?php

declare(strict_types=1);

namespace Backend\Domain;

class User
{
    const ADMIN = 'admin';
    private array $user;

    /**
     * User constructor
     * @return void 
     */
    public function __construct()
    {
        $this->user = $this->getUserSession();
    }

    /**
     * Get the user data
     * @return array 
     */
    public function getUser(): array
    {
        return $this->user;
    }

    /**
     * Check if the user is authenticated
     * @return bool 
     */
    public function isAdmin(): bool
    {
        return $this->user['role'] === User::ADMIN;
    }

    /**
     * Get the user session
     * @return array
     */
    public function getUserSession(): array
    {
        if (isset($_SESSION['user'])) {
            return (array) $_SESSION['user'];
        }

        return [];
    }
}
