<?php

namespace App\Controller\Api;

use App\Repository\BookingRepository;
use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/bookings', name: 'api_bookings_')]
class BookedDatesController extends AbstractController
{
    /**
     * Get booked dates for a session
     * 
     * GET /api/bookings/booked-dates/{sessionId}
     */
    #[Route('/booked-dates/{sessionId}', name: 'booked_dates', methods: ['GET'])]
    public function getBookedDates(int $sessionId, BookingRepository $bookingRepository, SessionRepository $sessionRepository): JsonResponse
    {
        $session = $sessionRepository->find($sessionId);
        
        if ($session === null) {
            return $this->json([
                'success' => false,
                'error' => 'Session not found'
            ], 404);
        }
        
        // Get all bookings for this session
        $bookings = $bookingRepository->findBy(['session' => $session]);
        
        // Extract booked dates (only confirmed bookings)
        $bookedDates = [];
        $fullyBookedDates = [];
        
        // Get session availability
        $availableFrom = $session->getAvailableFrom();
        $availableTo = $session->getAvailableTo();
        $totalAvailableMinutes = 0;
        
        if ($availableFrom !== null && $availableTo !== null) {
            $fromMinutes = (int) $availableFrom->format('H') * 60 + (int) $availableFrom->format('i');
            $toMinutes = (int) $availableTo->format('H') * 60 + (int) $availableTo->format('i');
            $totalAvailableMinutes = $toMinutes - $fromMinutes;
        }
        
        // Group bookings by date and calculate booked minutes per date
        $bookingsByDate = [];
        foreach ($bookings as $booking) {
            if ($booking->getPreferredDate() !== null && $booking->getStatus() !== 'cancelled') {
                $dateStr = $booking->getPreferredDate()->format('Y-m-d');
                
                if (!isset($bookingsByDate[$dateStr])) {
                    $bookingsByDate[$dateStr] = [
                        'bookedMinutes' => 0,
                        'bookings' => []
                    ];
                }
                
                if ($booking->getDurationMinutes() !== null) {
                    $bookingsByDate[$dateStr]['bookedMinutes'] += $booking->getDurationMinutes();
                }
                $bookingsByDate[$dateStr]['bookings'][] = $booking;
            }
        }
        
        // Determine which dates are booked and which are fully booked
        foreach ($bookingsByDate as $dateStr => $data) {
            $bookedDates[] = $dateStr;
            
            // Check if fully booked
            if ($totalAvailableMinutes > 0 && $data['bookedMinutes'] >= $totalAvailableMinutes) {
                $fullyBookedDates[] = $dateStr;
            }
        }
        
        // Get unique dates
        $bookedDates = array_unique($bookedDates);
        $bookedDates = array_values($bookedDates);
        $fullyBookedDates = array_unique($fullyBookedDates);
        $fullyBookedDates = array_values($fullyBookedDates);
        
        return $this->json([
            'success' => true,
            'data' => [
                'sessionId' => $sessionId,
                'bookedDates' => $bookedDates,
                'fullyBookedDates' => $fullyBookedDates,
                'count' => count($bookedDates),
                'totalAvailableMinutes' => $totalAvailableMinutes,
            ]
        ]);
    }
}
