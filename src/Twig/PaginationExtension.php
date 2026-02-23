<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class PaginationExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_pagination', [$this, 'renderPagination'], ['is_safe' => ['html']]),
            new TwigFunction('pagination_info', [$this, 'getPaginationInfo']),
            new TwigFunction('pagination_url', [$this, 'getPaginationUrl']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('paginate', [$this, 'paginateFilter']),
        ];
    }

    public function renderPagination(array $pagination, string $apiEndpoint, array $options = []): string
    {
        $containerId = $options['container_id'] ?? 'pagination-' . uniqid();
        $limit = $options['limit'] ?? $pagination['per_page'] ?? 10;
        $showInfo = $options['show_info'] ?? true;
        $showJumpTo = $options['show_jump_to'] ?? true;
        $maxVisiblePages = $options['max_visible_pages'] ?? 5;

        return sprintf(
            '<div id="%s" class="pagination-container" data-endpoint="%s" data-limit="%d" data-show-info="%s" data-show-jump="%s" data-max-visible="%d"></div>',
            htmlspecialchars($containerId),
            htmlspecialchars($apiEndpoint),
            $limit,
            $showInfo ? 'true' : 'false',
            $showJumpTo ? 'true' : 'false',
            $maxVisiblePages
        );
    }

    public function getPaginationInfo(array $pagination): array
    {
        return [
            'current_page' => $pagination['current_page'],
            'per_page' => $pagination['per_page'],
            'total' => $pagination['total'],
            'total_pages' => $pagination['total_pages'],
            'has_next_page' => $pagination['has_next_page'],
            'has_previous_page' => $pagination['has_previous_page'],
            'from' => $pagination['from'],
            'to' => $pagination['to'],
        ];
    }

    public function getPaginationUrl(string $baseUrl, int $page, array $params = []): string
    {
        $query = http_build_query(array_merge($params, ['page' => $page]));
        return $baseUrl . ($query ? '?' . $query : '');
    }

    public function paginateFilter(array $items, int $page = 1, int $limit = 10): array
    {
        $total = count($items);
        $totalPages = ceil($total / $limit);
        $offset = ($page - 1) * $limit;
        
        $paginatedItems = array_slice($items, $offset, $limit);

        return [
            'data' => $paginatedItems,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_previous_page' => $page > 1,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'previous_page' => $page > 1 ? $page - 1 : null,
                'first_page' => 1,
                'last_page' => $totalPages,
                'from' => $total > 0 ? $offset + 1 : null,
                'to' => min($offset + $limit, $total)
            ]
        ];
    }
}
