<?php

namespace App\Model;

class AccountData
{
    private string  $email;
    private string  $password;
    private array   $roles;

    /**
     * @param string $email
     * @param string $password
     * @param array $roles
     */
    public function __construct(string $email, string $password, array $roles)
    {
        $this->email = $email;
        $this->password = $password;
        $this->roles = $roles;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

}