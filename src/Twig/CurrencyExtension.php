<?php

namespace App\Twig;

use App\Service\CurrencyService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CurrencyExtension extends AbstractExtension
{
    public function __construct(private CurrencyService $currencyService)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('convertCurrency', [$this, 'convertCurrency']),
            new TwigFilter('formatPrice', [$this, 'formatPrice']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('convertPrice', [$this, 'convertPrice']),
            new TwigFunction('getCurrencySelector', [$this, 'getCurrencySelector']),
            new TwigFunction('getSupportedCurrencies', [$this, 'getSupportedCurrencies']),
        ];
    }

    public function convertCurrency(float $amount, string $toCurrency = 'EUR', string $fromCurrency = 'USD'): array
    {
        return $this->currencyService->convertPrice($amount, $fromCurrency, $toCurrency);
    }

    public function formatPrice(float $amount, string $currency = 'USD'): string
    {
        return $this->currencyService->formatPrice($amount, $currency);
    }

    public function getCurrencySelector(): array
    {
        return $this->currencyService->getCurrencySelector();
    }

    public function getSupportedCurrencies(): array
    {
        return $this->currencyService->getSupportedCurrencies();
    }
}
