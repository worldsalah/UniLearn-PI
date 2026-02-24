<?php

namespace App\Twig;

use App\Service\AIAnalystService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AIAnalystExtension extends AbstractExtension
{
    public function __construct(private AIAnalystService $aiAnalystService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getAIAnalysis', [$this, 'getAIAnalysis']),
            new TwigFunction('generateTrendInsights', [$this, 'generateTrendInsights']),
            new TwigFunction('getPerformanceColor', [$this, 'getPerformanceColor']),
            new TwigFunction('getTrendIcon', [$this, 'getTrendIcon']),
        ];
    }

    public function getAIAnalysis(array $stats, array $trends = []): array
    {
        return $this->aiAnalystService->analyzeMarketplaceData($stats, $trends);
    }

    public function generateTrendInsights(array $currentStats, array $previousStats): array
    {
        return $this->aiAnalystService->generateTrendInsights($currentStats, $previousStats);
    }

    public function getPerformanceColor(string $assessment): string
    {
        return match($assessment) {
            'Excellent' => 'success',
            'Good' => 'primary',
            'Fair' => 'warning',
            'Poor' => 'danger',
            default => 'secondary'
        };
    }

    public function getTrendIcon(float $change): string
    {
        if ($change > 0) {
            return '<i class="fas fa-arrow-up text-success"></i>';
        } elseif ($change < 0) {
            return '<i class="fas fa-arrow-down text-danger"></i>';
        } else {
            return '<i class="fas fa-minus text-muted"></i>';
        }
    }
}
