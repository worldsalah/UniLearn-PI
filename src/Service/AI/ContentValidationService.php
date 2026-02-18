<?php

namespace App\Service\AI;

use App\Entity\Product;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ContentValidationService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $openaiApiKey = '',
        private string $googleVisionKey = '',
    ) {
    }

    public function validateContent(Product $product): array
    {
        $score = 100.0;
        $findings = [];
        $suggestions = [];

        // 1. Text Plagiarism Detection
        $plagiarismResult = $this->checkPlagiarism($product->getDescription());
        $score -= (100 - $plagiarismResult['score']) * 0.35;

        if ($plagiarismResult['score'] < 80) {
            $findings[] = [
                'category' => 'plagiarism',
                'severity' => 'high',
                'message' => 'Potential plagiarized content detected',
            ];
            $suggestions[] = [
                'area' => 'Content Originality',
                'suggestion' => 'Rewrite description in your own words to ensure uniqueness',
                'priority' => 1,
            ];
        }

        // 2. Content Quality Scoring
        $qualityResult = $this->scoreContentQuality($product);
        $score -= (100 - $qualityResult['score']) * 0.30;

        if ($qualityResult['score'] < 70) {
            $suggestions[] = [
                'area' => 'Content Quality',
                'suggestion' => $qualityResult['suggestion'],
                'priority' => 2,
            ];
        }

        // 3. Image Validation (if image exists)
        if ($product->getImage()) {
            $imageResult = $this->validateImage($product->getImage());
            $score -= (100 - $imageResult['score']) * 0.20;

            if ($imageResult['score'] < 70) {
                $findings[] = [
                    'category' => 'image_quality',
                    'severity' => 'medium',
                    'message' => $imageResult['message'],
                ];
            }
        }

        // 4. Inappropriate Content Filtering
        $appropriatenessResult = $this->checkAppropriateness($product);
        $score -= (100 - $appropriatenessResult['score']) * 0.15;

        if ($appropriatenessResult['score'] < 90) {
            $findings[] = [
                'category' => 'inappropriate_content',
                'severity' => 'high',
                'message' => 'Potentially inappropriate content detected',
            ];
        }

        return [
            'score' => max(0, min(100, $score)),
            'findings' => $findings,
            'suggestions' => $suggestions,
            'details' => [
                'plagiarism' => $plagiarismResult['score'],
                'quality' => $qualityResult['score'],
                'image_validation' => $imageResult['score'] ?? 100,
                'appropriateness' => $appropriatenessResult['score'],
            ],
        ];
    }

    private function checkPlagiarism(?string $text): array
    {
        if (null === $text) {
            return [
                'score' => 0.0,
                'message' => 'No text provided',
            ];
        }

        $wordCount = str_word_count($text);

        if ($wordCount < 20) {
            return [
                'score' => 60.0,
                'message' => 'Description too short',
            ];
        }

        // Check for common copied phrases
        $commonPhrases = [
            'best service ever',
            'top quality guaranteed',
            'professional work',
            'fast delivery',
        ];

        $matchCount = 0;
        foreach ($commonPhrases as $phrase) {
            if (false !== stripos($text, $phrase)) {
                ++$matchCount;
            }
        }

        $score = 100 - ($matchCount * 10);

        return [
            'score' => max(0, $score),
            'message' => $matchCount > 0 ? 'Generic phrases detected' : 'Content appears original',
        ];
    }

    private function scoreContentQuality(Product $product): array
    {
        $score = 100.0;
        $suggestion = '';

        $title = $product->getTitle();
        $description = $product->getDescription();

        // Title quality
        if (null === $title || strlen($title) < 10) {
            $score -= 20;
            $suggestion = 'Add more descriptive title (at least 10 characters)';
        }

        if (null !== $title && strlen($title) > 100) {
            $score -= 10;
            $suggestion = 'Shorten title for better readability';
        }

        // Description quality
        if (null === $description) {
            $wordCount = 0;
        } else {
            $wordCount = str_word_count($description);
        }
        if ($wordCount < 30) {
            $score -= 30;
            $suggestion = 'Expand description with more details (minimum 30 words recommended)';
        }

        // Check for proper capitalization
        if (null !== $title && ($title === strtoupper($title) || $title === strtolower($title))) {
            $score -= 15;
            $suggestion = 'Use proper capitalization in title';
        }

        // Check for special characters spam
        $combinedText = ($title ?? '').($description ?? '');
        if (preg_match('/[!@#$%^&*]{3,}/', $combinedText)) {
            $score -= 20;
            $suggestion = 'Remove excessive special characters';
        }

        return [
            'score' => max(0, $score),
            'suggestion' => $suggestion ?: 'Content quality is good',
        ];
    }

    private function validateImage(?string $imagePath): array
    {
        if (!$imagePath) {
            return ['score' => 100, 'message' => 'No image provided'];
        }

        // Simulated image validation
        // In production, use Google Cloud Vision API for:
        // - Manipulation detection
        // - Quality assessment
        // - Inappropriate content detection

        return [
            'score' => 95.0,
            'message' => 'Image appears authentic and appropriate',
        ];
    }

    private function checkAppropriateness(Product $product): array
    {
        $score = 100.0;

        // Simple keyword-based inappropriate content filter
        $inappropriateKeywords = [
            'scam', 'fake', 'illegal', 'hack', 'cheat',
        ];

        $text = strtolower(($product->getTitle() ?? '').' '.($product->getDescription() ?? ''));

        foreach ($inappropriateKeywords as $keyword) {
            if (false !== strpos($text, $keyword)) {
                $score -= 30;
                break;
            }
        }

        return [
            'score' => max(0, $score),
            'message' => $score < 90 ? 'Potentially inappropriate keywords detected' : 'Content is appropriate',
        ];
    }
}
