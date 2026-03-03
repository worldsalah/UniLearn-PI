<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class PermissionChecker
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Check if current user has a specific permission
     */
    public function hasPermission(string $permissionName): bool
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        
        if ($user === null) {
            return false;
        }

        // Super admin has all permissions
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        $role = $user->getRole();
        if ($role === null) {
            return false;
        }

        return $role->hasPermission($permissionName);
    }

    /**
     * Check if current user has any of the given permissions
     */
    public function hasAnyPermission(array $permissionNames): bool
    {
        foreach ($permissionNames as $permissionName) {
            if ($this->hasPermission($permissionName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if current user has all of the given permissions
     */
    public function hasAllPermissions(array $permissionNames): bool
    {
        foreach ($permissionNames as $permissionName) {
            if (!$this->hasPermission($permissionName)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all permissions for current user
     */
    public function getUserPermissions(): array
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        
        if ($user === null) {
            return [];
        }

        $role = $user->getRole();
        if ($role === null) {
            return [];
        }

        return $role->getPermissions()->toArray();
    }
}
