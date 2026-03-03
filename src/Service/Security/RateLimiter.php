<?php

namespace App\Service\Security;

use Psr\Cache\CacheItemPoolInterface;

class RateLimiter
{
    private const LIMITS = [
        'booking_create' => ['limit' => 10, 'window' => 3600],     // 10 bookings/hour
        'review_create' => ['limit' => 20, 'window' => 3600],      // 20 reviews/hour
        'auth_login' => ['limit' => 5, 'window' => 300],           // 5 login attempts/5min
        'auth_register' => ['limit' => 3, 'window' => 3600],       // 3 registrations/hour
        'api_general' => ['limit' => 100, 'window' => 60],         // 100 requests/minute
    ];

    public function __construct(
        private CacheItemPoolInterface $cache
    ) {}

    /**
     * Check if action is within rate limit
     * 
     * @throws RateLimitException
     */
    public function checkLimit(string $action, string $identifier): bool
    {
        if (!isset(self::LIMITS[$action])) {
            return true;
        }

        $config = self::LIMITS[$action];
        $cacheKey = "rate_limit:{$action}:{$identifier}";
        
        $item = $this->cache->getItem($cacheKey);
        
        if (!$item->isHit()) {
            $item->set(1);
            $item->expiresAfter($config['window']);
            $this->cache->save($item);
            return true;
        }
        
        $current = $item->get();
        
        if ($current >= $config['limit']) {
            $ttl = $item->getMetadata()['ttl'] ?? $config['window'];
            throw new RateLimitException(
                "Rate limit exceeded for {$action}. Try again in {$ttl} seconds.",
                $config['limit'],
                $ttl
            );
        }
        
        $item->set($current + 1);
        $this->cache->save($item);
        
        return true;
    }

    /**
     * Get remaining requests for an action
     */
    public function getRemaining(string $action, string $identifier): int
    {
        if (!isset(self::LIMITS[$action])) {
            return PHP_INT_MAX;
        }

        $config = self::LIMITS[$action];
        $cacheKey = "rate_limit:{$action}:{$identifier}";
        
        $item = $this->cache->getItem($cacheKey);
        
        if (!$item->isHit()) {
            return $config['limit'];
        }
        
        return (int) max(0, $config['limit'] - $item->get());
    }

    /**
     * Reset rate limit for an action/identifier
     */
    public function reset(string $action, string $identifier): void
    {
        $cacheKey = "rate_limit:{$action}:{$identifier}";
        $this->cache->deleteItem($cacheKey);
    }

    /**
     * Get all rate limit configurations
     */
    public static function getLimits(): array
    {
        return self::LIMITS;
    }
}
