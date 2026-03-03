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

    public function __construct(private HttpClientInterface $httpClient)
    {
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

    private function fetchExchangeRates(): void
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiUrl . '?access_key=' . $this->apiKey);
            $data = $response->toArray();
            
            if (isset($data['rates'])) {
                $this->exchangeRates = $data['rates'];
                // Add USD as base rate (1:1)
                $this->exchangeRates['USD'] = 1.0;
            }
        } catch (\Exception $e) {
            // Fallback to default rates if API fails
            $this->exchangeRates = [
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
