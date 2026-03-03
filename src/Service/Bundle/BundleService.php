<?php

namespace App\Service\Bundle;

use App\Entity\Bundle;
use App\Entity\TeacherProfile;
use App\Entity\User;
use App\Enum\BundleType;
use App\Exception\BundleException;
use App\Repository\BundleRepository;
use Doctrine\ORM\EntityManagerInterface;

class BundleService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BundleRepository $bundleRepository
    ) {}

    /**
     * Purchase a new bundle
     */
    public function purchaseBundle(
        User $student,
        BundleType $type,
        ?TeacherProfile $teacher = null
    ): Bundle {
        // Calculate price
        $hourlyRate = $teacher !== null ? $teacher->getHourlyRateFloat() : 0;
        $price = $type->calculatePrice($hourlyRate);
        
        // Create bundle
        $bundle = new Bundle();
        $bundle->setStudent($student);
        $bundle->setType($type);
        $bundle->setPrice((string) $price);
        $bundle->setTeacher($teacher);
        
        // Set expiration date
        $expiresAt = new \DateTime();
        $expiresAt->modify("+{$type->expirationMonths()} months");
        $bundle->setExpiresAt($expiresAt);
        
        $this->entityManager->persist($bundle);
        $this->entityManager->flush();
        
        return $bundle;
    }

    /**
     * Get student's active bundles
     */
    public function getActiveBundles(User $student): array
    {
        return $this->bundleRepository->findBy([
            'student' => $student,
            'status' => Bundle::STATUS_ACTIVE
        ]);
    }

    /**
     * Get all bundles for a student
     */
    public function getStudentBundles(User $student): array
    {
        return $this->bundleRepository->findBy(
            ['student' => $student],
            ['createdAt' => 'DESC']
        );
    }

    /**
     * Check if bundle can be used for booking
     */
    public function canUseBundle(Bundle $bundle): bool
    {
        return $bundle->canUse();
    }

    /**
     * Get bundle usage history
     */
    public function getBundleUsageHistory(Bundle $bundle): array
    {
        return $bundle->getUsages()->toArray();
    }

    /**
     * Expire bundles that have passed their expiration date
     * Called by cron job
     */
    public function expireBundles(): int
    {
        $expired = $this->bundleRepository->findExpiredActive();
        $count = 0;
        
        foreach ($expired as $bundle) {
            $bundle->setStatus(Bundle::STATUS_EXPIRED);
            $count++;
        }
        
        $this->entityManager->flush();
        
        return $count;
    }

    /**
     * Get bundle statistics for a student
     */
    public function getBundleStats(User $student): array
    {
        $bundles = $this->bundleRepository->findBy(['student' => $student]);
        
        $stats = [
            'totalBundles' => count($bundles),
            'activeBundles' => 0,
            'totalSessions' => 0,
            'usedSessions' => 0,
            'remainingSessions' => 0,
            'totalSpent' => 0,
        ];
        
        foreach ($bundles as $bundle) {
            $stats['totalSessions'] += $bundle->getSessionsTotal();
            $stats['usedSessions'] += $bundle->getSessionsUsed();
            $stats['remainingSessions'] += $bundle->getSessionsRemaining();
            $stats['totalSpent'] += $bundle->getPriceFloat();
            
            if ($bundle->isActive()) {
                $stats['activeBundles']++;
            }
        }
        
        return $stats;
    }

    /**
     * Calculate bundle price preview
     */
    public function calculatePrice(BundleType $type, float $hourlyRate): array
    {
        return [
            'type' => $type->value,
            'sessions' => $type->sessions(),
            'hourlyRate' => $hourlyRate,
            'basePrice' => $hourlyRate * $type->sessions(),
            'discount' => $type->discount() * 100 . '%',
            'discountAmount' => $type->savings($hourlyRate),
            'finalPrice' => $type->calculatePrice($hourlyRate),
        ];
    }
}
