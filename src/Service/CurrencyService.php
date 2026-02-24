<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CurrencyService
{
    private string $apiKey = 'ce959b41ed1e15ff5f57064926e5d1d1';
    private string $apiUrl = 'https://api.exchangerate.host/latest';
    
    private array $exchangeRates = [];
    private array $supportedCurrencies = [
        'USD' => '$',
        'EUR' => '€', 
        'GBP' => '£',
        'JPY' => '¥',
        'CAD' => 'C$',
        'AUD' => 'A$',
        'CHF' => 'CHF',
        'CNY' => '¥',
        'INR' => '₹'
    ];

    public function __construct(private HttpClientInterface $httpClient, string $exchangerateApiKey = null)
    {
        // Use injected key if available, otherwise use default
        $this->apiKey = $exchangerateApiKey ?? $this->apiKey;
    }

    private function fetchExchangeRates(): void
    {
        try {
            // Try without API key first (free tier)
            $response = $this->httpClient->request('GET', $this->apiUrl, [
                'timeout' => 10
            ]);

            $data = $response->toArray();
            
            if (!isset($data['rates'])) {
                throw new \Exception('Invalid API response: No rates found');
            }

            if (empty($data['rates'])) {
                throw new \Exception('No exchange rates available');
            }

            $this->exchangeRates = $data['rates'];
            
        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Currency API Error: ' . $e->getMessage());
            
            // Use fallback rates if API fails
            $this->exchangeRates = $this->getFallbackRates();
        }
    }

    public function getExchangeRates(): array
    {
        if (empty($this->exchangeRates)) {
            $this->fetchExchangeRates();
        }
        
        return $this->exchangeRates;
    }

    public function convertPrice(float $amount, string $fromCurrency = 'USD', string $toCurrency = 'EUR'): array
    {
        if ($fromCurrency === $toCurrency) {
            return [
                'amount' => $amount,
                'formatted' => $this->formatPrice($amount, $fromCurrency),
                'currency' => $fromCurrency,
                'symbol' => $this->supportedCurrencies[$fromCurrency] ?? $fromCurrency
            ];
        }

        $rates = $this->getExchangeRates();
        
        // Convert to USD first if not USD
        $usdAmount = $fromCurrency === 'USD' ? $amount : $amount / ($rates[$fromCurrency] ?? 1);
        
        // Convert from USD to target currency
        $convertedAmount = $toCurrency === 'USD' ? $usdAmount : $usdAmount * ($rates[$toCurrency] ?? 1);

        return [
            'amount' => round($convertedAmount, 2),
            'formatted' => $this->formatPrice($convertedAmount, $toCurrency),
            'currency' => $toCurrency,
            'symbol' => $this->supportedCurrencies[$toCurrency] ?? $toCurrency
        ];
    }

    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    public function formatPrice(float $amount, string $currency = 'USD'): string
    {
        $symbol = $this->supportedCurrencies[$currency] ?? $currency;
        
        switch ($currency) {
            case 'EUR':
                return $symbol . number_format($amount, 2, ',', '.');
            case 'JPY':
            case 'CNY':
                return $symbol . number_format($amount, 0);
            case 'INR':
                return $symbol . number_format($amount, 2, '.', ',');
            default:
                return $symbol . number_format($amount, 2);
        }
    }

    private function getFallbackRates(): array
    {
        return [
            'USD' => 1.0,
            'EUR' => 0.85,
            'GBP' => 0.73,
            'JPY' => 110.0,
            'CAD' => 1.25,
            'AUD' => 1.35,
            'CHF' => 0.92,
            'CNY' => 6.45,
            'INR' => 74.0
        ];
    }

    public function getCurrencySelector(): array
    {
        $currencies = [];
        foreach ($this->supportedCurrencies as $code => $symbol) {
            $currencies[$code] = [
                'code' => $code,
                'symbol' => $symbol,
                'name' => $this->getCurrencyName($code)
            ];
        }
        return $currencies;
    }

    private function getCurrencyName(string $code): string
    {
        $names = [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'JPY' => 'Japanese Yen',
            'CAD' => 'Canadian Dollar',
            'AUD' => 'Australian Dollar',
            'CHF' => 'Swiss Franc',
            'CNY' => 'Chinese Yuan',
            'INR' => 'Indian Rupee'
        ];
        
        return $names[$code] ?? $code;
    }
}
