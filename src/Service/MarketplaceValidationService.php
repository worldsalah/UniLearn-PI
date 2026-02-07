<?php

namespace App\Service;

use App\Entity\Student;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\ValidationResult;
use App\Entity\TrustScore;
use App\Service\AI\SellerBehaviorAnalyzer;
use App\Service\AI\ContentValidationService;
use App\Service\AI\PricingAnalyzer;
use App\Service\AI\ReputationAggregator;
use App\Service\AI\TransactionPredictor;
use App\Repository\TrustScoreRepository;
use Doctrine\ORM\EntityManagerInterface;

class MarketplaceValidationService
{
    public function __construct(
        private SellerBehaviorAnalyzer $behaviorAnalyzer,
        private ContentValidationService $contentValidator,
        private PricingAnalyzer $pricingAnalyzer,
        private ReputationAggregator $reputationAggregator,
        private TransactionPredictor $transactionPredictor,
        private EntityManagerInterface $entityManager,
        private TrustScoreRepository $trustScoreRepository
    ) {}

    /**
     * Comprehensive seller validation
     */
    public function validateSeller(Student $seller): ValidationResult
    {
        $result = new ValidationResult();
        $result->setSeller($seller);
        $result->setValidationType('seller');

        // Run all seller-related validations
        $behaviorResult = $this->behaviorAnalyzer->analyzeBehavior($seller);
        $reputationResult = $this->reputationAggregator->aggregateReputation($seller);

        // Aggregate scores
        $componentScores = [
            'behavior' => $behaviorResult['score'],
            'reputation' => $reputationResult['score'],
        ];

        $overallScore = (
            $behaviorResult['score'] * 0.50 +
            $reputationResult['score'] * 0.50
        );

        $result->setComponentScores($componentScores);
        $result->setOverallScore($overallScore);
        $result->setRiskLevel($this->determineRiskLevel($overallScore));
        $result->setPassed($overallScore >= 60);

        // Aggregate findings
        foreach ($behaviorResult['findings'] as $finding) {
            $result->addFinding($finding['category'], $finding['severity'], $finding['message']);
        }
        foreach ($reputationResult['findings'] as $finding) {
            $result->addFinding($finding['category'], $finding['severity'], $finding['message']);
        }

        // Aggregate suggestions
        foreach ($behaviorResult['suggestions'] as $suggestion) {
            $result->addImprovementSuggestion($suggestion['area'], $suggestion['suggestion'], $suggestion['priority']);
        }
        foreach ($reputationResult['suggestions'] as $suggestion) {
            $result->addImprovementSuggestion($suggestion['area'], $suggestion['suggestion'], $suggestion['priority']);
        }

        // Update or create trust score
        $this->updateTrustScore($seller, $behaviorResult, $reputationResult);

        // Persist validation result
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return $result;
    }

    /**
     * Comprehensive product validation
     */
    public function validateProduct(Product $product): ValidationResult
    {
        $result = new ValidationResult();
        $result->setSeller($product->getFreelancer());
        $result->setProduct($product);
        $result->setValidationType('product');

        // Run all product-related validations
        $contentResult = $this->contentValidator->validateContent($product);
        $pricingResult = $this->pricingAnalyzer->analyzePricing($product);

        // Aggregate scores
        $componentScores = [
            'content' => $contentResult['score'],
            'pricing' => $pricingResult['score'],
        ];

        $overallScore = (
            $contentResult['score'] * 0.60 +
            $pricingResult['score'] * 0.40
        );

        $result->setComponentScores($componentScores);
        $result->setOverallScore($overallScore);
        $result->setRiskLevel($this->determineRiskLevel($overallScore));
        $result->setPassed($overallScore >= 70);

        // Aggregate findings
        foreach ($contentResult['findings'] as $finding) {
            $result->addFinding($finding['category'], $finding['severity'], $finding['message']);
        }
        foreach ($pricingResult['findings'] as $finding) {
            $result->addFinding($finding['category'], $finding['severity'], $finding['message']);
        }

        // Aggregate suggestions
        foreach ($contentResult['suggestions'] as $suggestion) {
            $result->addImprovementSuggestion($suggestion['area'], $suggestion['suggestion'], $suggestion['priority']);
        }
        foreach ($pricingResult['suggestions'] as $suggestion) {
            $result->addImprovementSuggestion($suggestion['area'], $suggestion['suggestion'], $suggestion['priority']);
        }

        // Persist validation result
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return $result;
    }

    /**
     * Pre-transaction risk assessment
     */
    public function assessTransaction(Student $seller, Product $product, ?User $buyer = null): array
    {
        $prediction = $this->transactionPredictor->predictTransactionSuccess($seller, $product, $buyer);

        return [
            'success_probability' => $prediction['success_probability'],
            'likelihood' => $prediction['likelihood'],
            'confidence' => $prediction['confidence'],
            'estimated_delivery_days' => $prediction['estimated_delivery_days'],
            'risk_level' => $prediction['risk_level'],
            'findings' => $prediction['findings'],
            'recommendations' => $prediction['suggestions'],
            'details' => $prediction['details'],
        ];
    }

    /**
     * Get or calculate trust score for seller
     */
    public function getTrustScore(Student $seller): TrustScore
    {
        $trustScore = $this->trustScoreRepository->findOneBy(['seller' => $seller]);

        if (!$trustScore) {
            // Create new trust score
            $trustScore = new TrustScore();
            $trustScore->setSeller($seller);
            
            // Calculate initial scores
            $behaviorResult = $this->behaviorAnalyzer->analyzeBehavior($seller);
            $reputationResult = $this->reputationAggregator->aggregateReputation($seller);
            
            $this->updateTrustScoreEntity($trustScore, $behaviorResult, $reputationResult);
            
            $this->entityManager->persist($trustScore);
            $this->entityManager->flush();
        }

        return $trustScore;
    }

    /**
     * Generate improvement suggestions for seller
     */
    public function generateImprovementSuggestions(Student $seller): array
    {
        $suggestions = [];

        // Analyze all aspects
        $behaviorResult = $this->behaviorAnalyzer->analyzeBehavior($seller);
        $reputationResult = $this->reputationAggregator->aggregateReputation($seller);

        // Collect all suggestions
        $allSuggestions = array_merge(
            $behaviorResult['suggestions'],
            $reputationResult['suggestions']
        );

        // Sort by priority
        usort($allSuggestions, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return [
            'overall_score' => ($behaviorResult['score'] + $reputationResult['score']) / 2,
            'suggestions' => $allSuggestions,
            'strengths' => $this->identifyStrengths($behaviorResult, $reputationResult),
            'weaknesses' => $this->identifyWeaknesses($behaviorResult, $reputationResult),
        ];
    }

    private function updateTrustScore(Student $seller, array $behaviorResult, array $reputationResult): void
    {
        $trustScore = $this->trustScoreRepository->findOneBy(['seller' => $seller]);

        if (!$trustScore) {
            $trustScore = new TrustScore();
            $trustScore->setSeller($seller);
        }

        $this->updateTrustScoreEntity($trustScore, $behaviorResult, $reputationResult);

        $this->entityManager->persist($trustScore);
        $this->entityManager->flush();
    }

    private function updateTrustScoreEntity(TrustScore $trustScore, array $behaviorResult, array $reputationResult): void
    {
        $trustScore->setBehaviorScore($behaviorResult['score']);
        $trustScore->setReputationScore($reputationResult['score']);
        
        // Set content and pricing scores to neutral if not available
        if ($trustScore->getContentScore() == 0) {
            $trustScore->setContentScore(75.0);
        }
        if ($trustScore->getPricingScore() == 0) {
            $trustScore->setPricingScore(75.0);
        }

        $trustScore->setScoreBreakdown([
            'behavior' => $behaviorResult['details'] ?? [],
            'reputation' => $reputationResult['details'] ?? [],
        ]);

        $trustScore->calculateOverallScore();
    }

    private function determineRiskLevel(float $score): string
    {
        if ($score >= 75) {
            return 'low';
        } elseif ($score >= 50) {
            return 'medium';
        }
        return 'high';
    }

    private function identifyStrengths(array $behaviorResult, array $reputationResult): array
    {
        $strengths = [];

        if ($behaviorResult['score'] >= 80) {
            $strengths[] = 'Excellent seller behavior and transaction patterns';
        }
        if ($reputationResult['score'] >= 80) {
            $strengths[] = 'Strong reputation and community standing';
        }

        return $strengths;
    }

    private function identifyWeaknesses(array $behaviorResult, array $reputationResult): array
    {
        $weaknesses = [];

        if ($behaviorResult['score'] < 60) {
            $weaknesses[] = 'Concerning behavior patterns detected';
        }
        if ($reputationResult['score'] < 60) {
            $weaknesses[] = 'Limited reputation or verification';
        }

        return $weaknesses;
    }
}
