<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\User;

/**
 * Service métier pour la gestion des réservations
 * Règles métier:
 * 1. L'utilisateur doit être spécifié
 * 2. La date de réservation doit être dans le futur
 * 3. L'utilisateur ne peut pas avoir de conflit de réservation
 */
class BookingManager
{
    public function validate(Booking $booking): bool
    {
        if ($booking->getUser() === null) {
            throw new \InvalidArgumentException('L\'utilisateur est obligatoire');
        }
        
        $preferredDate = $booking->getPreferredDate();
        if ($preferredDate !== null && $preferredDate < new \DateTime()) {
            throw new \InvalidArgumentException('La date de réservation doit être dans le futur');
        }
        
        return true;
    }
    
    public function canCancel(Booking $booking, User $user): bool
    {
        if ($booking->getUser() !== $user) {
            throw new \InvalidArgumentException('Seul l\'utilisateur ayant fait la réservation peut l\'annuler');
        }
        
        $preferredDate = $booking->getPreferredDate();
        if ($preferredDate !== null) {
            $now = new \DateTime();
            $interval = $now->diff($preferredDate);
            if ($interval->days < 1 && $preferredDate > $now) {
                throw new \InvalidArgumentException('La réservation ne peut pas être annulée moins de 24h avant');
            }
        }
        
        return true;
    }
}
