<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class RecommendationExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_recommendations', [$this, 'renderRecommendations'], ['is_safe' => ['html']]),
            new TwigFunction('recommendation_container', [$this, 'getRecommendationContainer']),
            new TwigFunction('track_product_view', [$this, 'trackProductView']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('recommendation_badge', [$this, 'getRecommendationBadge']),
        ];
    }

    public function renderRecommendations(array $options = []): string
    {
        $type = $options['type'] ?? 'recommended';
        $title = $options['title'] ?? $this->getDefaultTitle($type);
        $limit = $options['limit'] ?? 5;
        $userId = $options['user_id'] ?? null;
        $showCarousel = $options['carousel'] ?? true;
        $containerId = $options['container_id'] ?? $this->generateContainerId($type);

        return sprintf(
            '<div id="%s" class="recommendation-container" data-recommendation-type="%s" data-limit="%d" data-user-id="%s" data-carousel="%s">
                <div class="recommendation-loading">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2">Loading %s...</span>
                </div>
            </div>',
            htmlspecialchars($containerId),
            htmlspecialchars($type),
            $limit,
            $userId ? htmlspecialchars($userId) : 'null',
            $showCarousel ? 'true' : 'false',
            htmlspecialchars($title)
        );
    }

    public function getRecommendationContainer(string $type, array $options = []): string
    {
        $containerId = $options['container_id'] ?? $this->generateContainerId($type);
        return $containerId;
    }

    public function trackProductView(int $productId, array $options = []): string
    {
        $userId = $options['user_id'] ?? 'null';
        $autoTrack = $options['auto_track'] ?? 'true';

        return sprintf(
            '<div class="product-view-tracker" data-product-id="%d" data-user-id="%s" data-auto-track="%s"></div>',
            $productId,
            $userId,
            $autoTrack
        );
    }

    public function getRecommendationBadge(string $type, string $text = ''): string
    {
        $badges = [
            'recommended' => [
                'class' => 'badge bg-primary',
                'icon' => 'fas fa-star',
                'text' => 'Recommended'
            ],
            'recently_viewed' => [
                'class' => 'badge bg-secondary',
                'icon' => 'fas fa-clock',
                'text' => 'Recently Viewed'
            ],
            'also_bought' => [
                'class' => 'badge bg-success',
                'icon' => 'fas fa-shopping-cart',
                'text' => 'People Also Bought'
            ],
            'trending' => [
                'class' => 'badge bg-danger',
                'icon' => 'fas fa-fire',
                'text' => 'Trending'
            ]
        ];

        $badge = $badges[$type] ?? $badges['recommended'];
        $displayText = $text ?: $badge['text'];

        return sprintf(
            '<span class="%s"><i class="%s me-1"></i>%s</span>',
            $badge['class'],
            $badge['icon'],
            htmlspecialchars($displayText)
        );
    }

    private function getDefaultTitle(string $type): string
    {
        $titles = [
            'recommended' => '🛍 Recommended for you',
            'recently_viewed' => '🛍 Recently Viewed',
            'also_bought' => '🛍 People also bought this',
            'trending' => '🔥 Trending Now',
            'popular' => '⭐ Popular Products'
        ];

        return $titles[$type] ?? $titles['recommended'];
    }

    private function generateContainerId(string $type): string
    {
        return sprintf('recommendation-%s-%s', $type, uniqid());
    }
}
