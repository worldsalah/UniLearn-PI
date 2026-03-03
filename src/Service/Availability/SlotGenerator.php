<?php

namespace App\Service\Availability;

use App\Entity\Availability;
use App\Entity\TeacherProfile;
use App\Entity\TimeSlot;
use App\Repository\AvailabilityRepository;
use App\Repository\TimeSlotRepository;
use Doctrine\ORM\EntityManagerInterface;

class SlotGenerator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AvailabilityRepository $availabilityRepository,
        private TimeSlotRepository $timeSlotRepository
    ) {}

    /**
     * Generate time slots for a date range
     */
    public function generateForDateRange(TeacherProfile $teacher, \DateTime $startDate, \DateTime $endDate): int
    {
        $generated = 0;
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $generated += $this->generateForDate($teacher, clone $currentDate);
            $currentDate->modify('+1 day');
        }
        
        $this->entityManager->flush();
        
        return $generated;
    }

    /**
     * Generate time slots for a specific date
     */
    public function generateForDate(TeacherProfile $teacher, \DateTime $date): int
    {
        $dayOfWeek = (int) $date->format('N') - 1; // 0=Monday, 6=Sunday
        
        // Get availability for this day
        $availabilities = $this->availabilityRepository->findBy([
            'teacher' => $teacher,
            'dayOfWeek' => $dayOfWeek,
            'isActive' => true
        ]);
        
        $generated = 0;
        
        foreach ($availabilities as $availability) {
            $generated += $this->generateSlotsForAvailability($availability, $date);
        }
        
        return $generated;
    }

    /**
     * Generate slots from an availability entry
     */
    private function generateSlotsForAvailability(Availability $availability, \DateTime $date): int
    {
        $generated = 0;
        $slotDuration = 60; // 1-hour slots
        
        $availabilityStart = $availability->getStartTime();
        $availabilityEnd = $availability->getEndTime();
        
        if ($availabilityStart === null || $availabilityEnd === null) {
            return 0;
        }
        
        $startTime = clone $availabilityStart;
        $endTime = clone $availabilityEnd;
        
        while ($startTime < $endTime) {
            $slotEnd = clone $startTime;
            $slotEnd->modify("+{$slotDuration} minutes");
            
            // Don't create slot that extends past availability end
            if ($slotEnd > $endTime) {
                break;
            }
            
            // Check if slot already exists
            $existingSlot = $this->timeSlotRepository->findOneBy([
                'availability' => $availability,
                'date' => $date,
                'startTime' => $startTime
            ]);
            
            if ($existingSlot === null) {
                $slot = new TimeSlot();
                $slot->setAvailability($availability);
                $slot->setDate($date);
                $slot->setStartTime(clone $startTime);
                $slot->setEndTime(clone $slotEnd);
                $slot->setStatus(TimeSlot::STATUS_AVAILABLE);
                
                $this->entityManager->persist($slot);
                $generated++;
            }
            
            $startTime = $slotEnd;
        }
        
        return $generated;
    }

    /**
     * Generate slots for the next N weeks
     */
    public function generateForNextWeeks(TeacherProfile $teacher, int $weeks = 4): int
    {
        $startDate = new \DateTime();
        $endDate = (clone $startDate)->modify("+{$weeks} weeks");
        
        return $this->generateForDateRange($teacher, $startDate, $endDate);
    }

    /**
     * Clean up old unused slots
     */
    public function cleanupOldSlots(int $daysOld = 30): int
    {
        $cutoffDate = (new \DateTime())->modify("-{$daysOld} days");
        
        $oldSlots = $this->timeSlotRepository->findOldUnusedSlots($cutoffDate);
        
        $count = 0;
        foreach ($oldSlots as $slot) {
            $this->entityManager->remove($slot);
            $count++;
        }
        
        $this->entityManager->flush();
        
        return $count;
    }
}
