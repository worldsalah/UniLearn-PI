<?php

namespace App\Service\Availability;

use App\Entity\Availability;
use App\Entity\TeacherProfile;
use App\Entity\TimeSlot;
use App\Repository\AvailabilityRepository;
use App\Repository\TimeSlotRepository;
use Doctrine\ORM\EntityManagerInterface;

class AvailabilityService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AvailabilityRepository $availabilityRepository,
        private TimeSlotRepository $timeSlotRepository,
        private SlotGenerator $slotGenerator
    ) {}

    /**
     * Set teacher's weekly availability
     */
    public function setAvailability(TeacherProfile $teacher, array $availabilities): array
    {
        // Clear existing availability for this teacher first
        $existing = $this->availabilityRepository->findBy(['teacher' => $teacher]);
        foreach ($existing as $old) {
            $this->entityManager->remove($old);
        }
        $this->entityManager->flush();
        
        $created = [];
        
        foreach ($availabilities as $data) {
            $availability = new Availability();
            $availability->setTeacher($teacher);
            $availability->setDayOfWeek($data['dayOfWeek']);
            $availability->setStartTime(new \DateTime($data['startTime']));
            $availability->setEndTime(new \DateTime($data['endTime']));
            $availability->setIsActive($data['isActive'] ?? true);
            
            // Validate time range
            if (!$availability->isValidTimeRange()) {
                throw new \InvalidArgumentException('End time must be after start time');
            }
            
            $this->entityManager->persist($availability);
            $created[] = $availability;
        }
        
        $this->entityManager->flush();
        
        return $created;
    }

    /**
     * Update a single availability entry
     */
    public function updateAvailability(Availability $availability, array $data): Availability
    {
        if (isset($data['dayOfWeek'])) {
            $availability->setDayOfWeek($data['dayOfWeek']);
        }
        
        if (isset($data['startTime'])) {
            $availability->setStartTime(new \DateTime($data['startTime']));
        }
        
        if (isset($data['endTime'])) {
            $availability->setEndTime(new \DateTime($data['endTime']));
        }
        
        if (isset($data['isActive'])) {
            $availability->setIsActive($data['isActive']);
        }
        
        if (!$availability->isValidTimeRange()) {
            throw new \InvalidArgumentException('End time must be after start time');
        }
        
        $this->entityManager->flush();
        
        return $availability;
    }

    /**
     * Delete availability
     */
    public function deleteAvailability(Availability $availability): void
    {
        // Check if there are any booked slots
        $hasBookedSlots = $this->timeSlotRepository->hasBookedSlots($availability);
        
        if ($hasBookedSlots) {
            throw new \LogicException('Cannot delete availability with booked sessions');
        }
        
        $this->entityManager->remove($availability);
        $this->entityManager->flush();
    }

    /**
     * Get available slots for a teacher within date range
     */
    public function getAvailableSlots(TeacherProfile $teacher, \DateTime $startDate, \DateTime $endDate): array
    {
        $teacherId = $teacher->getId();
        if ($teacherId === null) {
            return [];
        }
        
        // Generate slots if not already generated
        $this->slotGenerator->generateForDateRange($teacher, $startDate, $endDate);
        
        return $this->timeSlotRepository->findAvailableByTeacher(
            $teacherId,
            $startDate,
            $endDate
        );
    }

    /**
     * Get teacher's weekly schedule
     */
    public function getWeeklySchedule(TeacherProfile $teacher): array
    {
        return $this->availabilityRepository->findBy([
            'teacher' => $teacher,
            'isActive' => true
        ], ['dayOfWeek' => 'ASC', 'startTime' => 'ASC']);
    }

    /**
     * Block a specific time slot
     */
    public function blockSlot(TimeSlot $slot): TimeSlot
    {
        if (!$slot->isAvailable()) {
            throw new \LogicException('Can only block available slots');
        }
        
        $slot->block();
        $this->entityManager->flush();
        
        return $slot;
    }

    /**
     * Unblock a time slot
     */
    public function unblockSlot(TimeSlot $slot): TimeSlot
    {
        $slot->release();
        $this->entityManager->flush();
        
        return $slot;
    }

    /**
     * Validate no overlap with existing availability
     */
    private function validateNoOverlap(TeacherProfile $teacher, Availability $newAvailability): void
    {
        $existing = $this->availabilityRepository->findBy([
            'teacher' => $teacher,
            'dayOfWeek' => $newAvailability->getDayOfWeek(),
            'isActive' => true
        ]);
        
        foreach ($existing as $avail) {
            // Skip if updating the same availability
            $newId = $newAvailability->getId();
            $availId = $avail->getId();
            if ($newId !== null && $newId === $availId) {
                continue;
            }
            
            if ($newAvailability->overlapsWith($avail)) {
                throw new \InvalidArgumentException('Availability overlaps with existing schedule');
            }
        }
    }
}
