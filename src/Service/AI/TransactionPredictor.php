<?php

namespace App\Service\AI;

use App\Entity\Product;
use App\Entity\User;

class TransactionPredictor
{
    public function predictTransactionSuccess(User $seller, Product $product, ?User $buyer = null): array
    {
        $score = 100.0;
        $findings = [];
        $suggestions = [];

        // 1. Seller Reliability Score (40% weight)
        $sellerScore = $this->assessSellerReliability($seller);
        $score -= (100 - $sellerScore) * 0.40;

        // 2. Product Quality Score (30% weight)
        $productScore = $this->assessProductQuality($product);
        $score -= (100 - $productScore) * 0.30;

        // 3. Price Reasonableness (20% weight)
        $priceScore = $this->assessPriceReasonableness($product);
        $score -= (100 - $priceScore) * 0.20;

        // 4. Buyer-Seller Compatibility (10% weight)
        if ($buyer) {
            $compatibilityScore = $this->assessCompatibility($seller, $buyer);
            $score -= (100 - $compatibilityScore) * 0.10;
        }

        // Determine success likelihood
        $likelihood = 'high';
        $confidence = 'high';
        
        if ($score < 50) {
            $likelihood = 'low';
            $confidence = 'high';
            $findings[] = [
                'category' => 'transaction_risk',
                'severity' => 'high',
                'message' => 'High risk transaction - proceed with caution'
            ];
        } elseif ($score < 70) {
            $likelihood = 'medium';
            $confidence = 'medium';
            $findings[] = [
                'category' => 'transaction_risk',
                'severity' => 'medium',
                'message' => 'Moderate risk transaction'
            ];
        }

        // Generate recommendations
        if ($sellerScore < 70) {
            $suggestions[] = [
                'area' => 'Seller Selection',
                'suggestion' => 'Consider choosing a seller with higher ratings',
                'priority' => 1
            ];
        }

        if ($productScore < 70) {
            $suggestions[] = [
                'area' => 'Product Quality',
                'suggestion' => 'Request more details about the service before purchasing',
                'priority' => 2
            ];
        }

        return [
            'success_probability' => $score,
            'likelihood' => $likelihood,
            'confidence' => $confidence,
            'estimated_delivery_days' => $this->estimateDeliveryTime($seller, $product),
            'risk_level' => $score >= 70 ? 'low' : ($score >= 50 ? 'medium' : 'high'),
            'findings' => $findings,
            'suggestions' => $suggestions,
            'details' => [
                'seller_reliability' => $sellerScore,
                'product_quality' => $productScore,
                'price_reasonableness' => $priceScore,
                'compatibility' => $buyer ? $compatibilityScore : 100,
            ]
        ];
    }

    private function assessSellerReliability(User $seller): float
    {
        $score = 0.0;
        
        // Rating (0-5 to 0-60 points) - User entity doesn't have getRating method
        $score += 60.0; // Assume decent rating
        
        // Account age (up to 20 points)
        $createdAt = $seller->getCreatedAt();
        if ($createdAt === null) {
            return 5.0;
        }
        $accountAge = $createdAt->diff(new \DateTimeImmutable())->days;
        if ($accountAge > 180) {
            $score += 20;
        } elseif ($accountAge > 90) {
            $score += 15;
        } elseif ($accountAge > 30) {
            $score += 10;
        } else {
            $score += 5;
        }
        
        // Product count (up to 20 points)
        $productCount = $seller->getProducts()->count();
        $score += min(20, $productCount * 4);
        
        return min(100, $score);
    }

    private function assessProductQuality(Product $product): float
    {
        $score = 100.0;
        
        // Description quality
        $descLength = strlen($product->getDescription());
        if ($descLength < 50) {
            $score -= 40;
        } elseif ($descLength < 100) {
            $score -= 20;
        }
        
        // Title quality
        $titleLength = strlen($product->getTitle());
        if ($titleLength < 10) {
            $score -= 20;
        }
        
        // Has image
        if (!$product->getImage()) {
            $score -= 15;
        }
        
        return max(0, $score);
    }

    private function assessPriceReasonableness(Product $product): float
    {
        $price = $product->getPrice();
        
        // Very low prices are suspicious
        if ($price < 5) {
            return 30.0;
        }
        
        // Extremely high prices are risky
        if ($price > 500) {
            return 60.0;
        }
        
        // Reasonable price range
        return 95.0;
    }

    private function assessCompatibility(User $seller, User $buyer): float
    {
        // Simulated compatibility check
        // In production, could analyze:
        // - Previous interactions
        // - Communication style
        // - Timezone compatibility
        // - Language preferences
        
        return 85.0; // Default good compatibility
    }

    private function estimateDeliveryTime(User $seller, Product $product): int
    {
        // Base delivery time
        $days = 7;
        
        // Adjust based on seller rating - User entity doesn't have getRating method
        // Skip rating adjustment for now
        
        // Adjust based on price (higher price = more complex = longer)
        if ($product->getPrice() > 200) {
            $days += 5;
        } elseif ($product->getPrice() > 100) {
            $days += 2;
        }
        
        return max(1, $days);
    }
}
