<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Trait providing helper methods for controllers.
 */
trait ControllerTrait
{
    /**
     * Safely check if a user object is not null.
     */
    protected function isUserNotNull(?UserInterface $user): bool
    {
        return $user !== null;
    }

    /**
     * Safely check if a user object is null.
     */
    protected function isUserNull(?UserInterface $user): bool
    {
        return $user === null;
    }

    /**
     * Get the User entity from UserInterface.
     */
    protected function getUserEntity(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }

    /**
     * Safely check if entity is not null.
     */
    protected function isEntityNotNull(?object $entity): bool
    {
        return $entity !== null;
    }

    /**
     * Safely check if entity is null.
     */
    protected function isEntityNull(?object $entity): bool
    {
        return $entity === null;
    }
}
