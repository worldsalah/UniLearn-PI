<?php

namespace App\Tests\Service;

use App\Entity\Booking;
use App\Entity\User;
use App\Service\BookingManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour BookingManager
 * Règles métier validées:
 * 1. L'utilisateur doit être spécifié
 * 2. La date de réservation doit être dans le futur
 */
class BookingManagerTest extends TestCase
{
    public function testValidBooking(): void
    {
        $user = new User();
        $user->setFullName('Test User');
        
        $booking = new Booking();
        $booking->setUser($user);
        $booking->setPreferredDate(new \DateTime('+1 day'));
        
        $manager = new BookingManager();
        $this->assertTrue($manager->validate($booking));
    }

    public function testBookingWithoutUser(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'utilisateur est obligatoire');
        
        $booking = new Booking();
        $booking->setPreferredDate(new \DateTime('+1 day'));
        
        $manager = new BookingManager();
        $manager->validate($booking);
    }

    public function testBookingWithPastDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de réservation doit être dans le futur');
        
        $user = new User();
        $user->setFullName('Test User');
        
        $booking = new Booking();
        $booking->setUser($user);
        $booking->setPreferredDate(new \DateTime('-1 day'));
        
        $manager = new BookingManager();
        $manager->validate($booking);
    }

    public function testCanCancelOwnBooking(): void
    {
        $user = new User();
        $user->setFullName('Test User');
        
        $booking = new Booking();
        $booking->setUser($user);
        $booking->setPreferredDate(new \DateTime('+2 days'));
        
        $manager = new BookingManager();
        $this->assertTrue($manager->canCancel($booking, $user));
    }

    public function testCannotCancelOtherUserBooking(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Seul l\'utilisateur ayant fait la réservation peut l\'annuler');
        
        $user1 = new User();
        $user1->setFullName('User 1');
        
        $user2 = new User();
        $user2->setFullName('User 2');
        
        $booking = new Booking();
        $booking->setUser($user1);
        $booking->setPreferredDate(new \DateTime('+2 days'));
        
        $manager = new BookingManager();
        $manager->canCancel($booking, $user2);
    }
}
