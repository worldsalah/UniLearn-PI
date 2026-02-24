<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;

class TranslationApiService
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private string $apiKey;
    private string $baseUrl = 'https://translation.googleapis.com/language/translate/v2';

    public function __construct(HttpClientInterface $client, LoggerInterface $logger, string $googleTranslateApiKey = null)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->apiKey = $googleTranslateApiKey ?: $this->getFallbackApiKey();
    }

    /**
     * Translate text using Google Translate API
     *
     * @param string $text Text to translate
     * @param string $targetLanguage Target language code (e.g., 'fr', 'es', 'de')
     * @param string $sourceLanguage Source language code (optional, auto-detected if null)
     * @return array Response with translation data
     */
    public function translateText(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        try {
            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'error' => 'Google Translate API key not configured',
                    'details' => 'Please set GOOGLE_TRANSLATE_API_KEY in your environment variables'
                ];
            }

            // Prepare request data
            $requestData = [
                'q' => $text,
                'target' => $targetLanguage,
                'format' => 'text',
                'source' => $sourceLanguage ?: 'auto'
            ];

            // Make API request
            $response = $this->client->request('POST', $this->baseUrl, [
                'query' => [
                    'key' => $this->apiKey,
                ],
                'json' => $requestData,
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['data']['translations']) && !empty($data['data']['translations'])) {
                $translation = $data['data']['translations'][0];
                
                return [
                    'success' => true,
                    'translatedText' => $translation['translatedText'] ?? '',
                    'detectedSourceLanguage' => $translation['detectedSourceLanguage'] ?? $sourceLanguage,
                    'targetLanguage' => $targetLanguage,
                    'originalText' => $text,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $data['error']['message'] ?? 'Translation failed',
                    'details' => $data['error']['details'] ?? 'Unknown error occurred'
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error('Google Translate API Error: ' . $e->getMessage(), [
                'exception' => $e,
                'text' => $text,
                'target_language' => $targetLanguage,
                'source_language' => $sourceLanguage,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to connect to translation service',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Translate multiple texts in batch
     *
     * @param array $texts Array of texts to translate
     * @param string $targetLanguage Target language code
     * @param string $sourceLanguage Source language code (optional)
     * @return array Response with batch translation data
     */
    public function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        try {
            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'error' => 'Google Translate API key not configured',
                    'details' => 'Please set GOOGLE_TRANSLATE_API_KEY in your environment variables'
                ];
            }

            $translations = [];
            $errors = [];

            foreach ($texts as $index => $text) {
                $requestData = [
                    'q' => $text,
                    'target' => $targetLanguage,
                    'format' => 'text',
                    'source' => $sourceLanguage ?: 'auto'
                ];

                $response = $this->client->request('POST', $this->baseUrl, [
                    'query' => [
                        'key' => $this->apiKey,
                    ],
                    'json' => $requestData,
                    'timeout' => 30,
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]);

                $data = $response->toArray();

                if (isset($data['data']['translations']) && !empty($data['data']['translations'])) {
                    $translation = $data['data']['translations'][0];
                    $translations[] = [
                        'index' => $index,
                        'originalText' => $text,
                        'translatedText' => $translation['translatedText'] ?? '',
                        'detectedSourceLanguage' => $translation['detectedSourceLanguage'] ?? $sourceLanguage,
                    ];
                } else {
                    $errors[] = [
                        'index' => $index,
                        'originalText' => $text,
                        'error' => $data['error']['message'] ?? 'Translation failed',
                    ];
                }

                // Add small delay to avoid rate limiting
                usleep(100000); // 0.1 seconds
            }

            return [
                'success' => empty($errors),
                'translations' => $translations,
                'errors' => $errors,
                'targetLanguage' => $targetLanguage,
                'totalProcessed' => count($texts),
                'successfulTranslations' => count($translations),
            ];

        } catch (\Exception $e) {
            $this->logger->error('Google Translate Batch API Error: ' . $e->getMessage(), [
                'exception' => $e,
                'texts_count' => count($texts),
                'target_language' => $targetLanguage,
                'source_language' => $sourceLanguage,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to connect to translation service',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Get supported languages
     *
     * @return array Response with supported languages
     */
    public function getSupportedLanguages(): array
    {
        try {
            $response = $this->client->request('GET', $this->baseUrl . '/languages', [
                'query' => [
                    'key' => $this->apiKey,
                    'target' => 'en', // Get language names in English
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();

            if (isset($data['data']['languages'])) {
                $languages = [];
                foreach ($data['data']['languages'] as $lang) {
                    $languages[] = [
                        'code' => $lang['language'] ?? '',
                        'name' => $lang['name'] ?? '',
                        'nativeName' => $lang['nativeName'] ?? '',
                        'supportedForTranslation' => $lang['supportSource'] ?? false && $lang['supportTarget'] ?? false,
                    ];
                }

                return [
                    'success' => true,
                    'languages' => $languages,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $data['error']['message'] ?? 'Failed to fetch supported languages',
                    'details' => $data['error']['details'] ?? 'Unknown error occurred'
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error('Google Translate Languages API Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch supported languages',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Detect language of text
     *
     * @param string $text Text to analyze
     * @return array Response with detected language
     */
    public function detectLanguage(string $text): array
    {
        try {
            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'error' => 'Google Translate API key not configured',
                    'details' => 'Please set GOOGLE_TRANSLATE_API_KEY in your environment variables'
                ];
            }

            $response = $this->client->request('POST', $this->baseUrl . '/detect', [
                'query' => [
                    'key' => $this->apiKey,
                ],
                'json' => [
                    'q' => $text,
                    'format' => 'text',
                ],
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['data']['detections']) && !empty($data['data']['detections'])) {
                $detection = $data['data']['detections'][0];
                
                return [
                    'success' => true,
                    'detectedLanguage' => $detection['language'] ?? '',
                    'confidence' => $detection['confidence'] ?? 0,
                    'isReliable' => ($detection['confidence'] ?? 0) > 0.7,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $data['error']['message'] ?? 'Language detection failed',
                    'details' => $data['error']['details'] ?? 'Unknown error occurred'
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error('Google Translate Detection API Error: ' . $e->getMessage(), [
                'exception' => $e,
                'text' => $text,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to detect language',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Get fallback API key (for demo purposes)
     * In production, this should come from environment variables
     */
    private function getFallbackApiKey(): string
    {
        // For demo purposes - in production, use environment variables
        return $_ENV['GOOGLE_TRANSLATE_API_KEY'] ?? 'demo-key-replace-with-real-api-key';
    }

    /**
     * Translate question options (A, B, C, D)
     *
     * @param array $options Question options to translate
     * @param string $targetLanguage Target language
     * @param string $sourceLanguage Source language (optional)
     * @return array Response with translated options
     */
    public function translateQuestionOptions(array $options, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        try {
            $translatedOptions = [];
            $errors = [];

            foreach ($options as $key => $option) {
                if (empty($option)) {
                    $translatedOptions[$key] = '';
                    continue;
                }

                $result = $this->translateText($option, $targetLanguage, $sourceLanguage);
                
                if ($result['success']) {
                    $translatedOptions[$key] = $result['translatedText'];
                } else {
                    $errors[] = [
                        'option' => $key,
                        'text' => $option,
                        'error' => $result['error'],
                    ];
                    $translatedOptions[$key] = $option; // Fallback to original
                }

                // Add small delay to avoid rate limiting
                usleep(50000); // 0.05 seconds
            }

            return [
                'success' => empty($errors),
                'translatedOptions' => $translatedOptions,
                'errors' => $errors,
                'targetLanguage' => $targetLanguage,
                'originalOptions' => $options,
            ];

        } catch (\Exception $e) {
            $this->logger->error('Question Options Translation Error: ' . $e->getMessage(), [
                'exception' => $e,
                'options' => $options,
                'target_language' => $targetLanguage,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to translate question options',
                'details' => $e->getMessage(),
                'translatedOptions' => $options, // Fallback to original
            ];
        }
    }

    /**
     * Get language code from common language names
     *
     * @param string $languageName Language name (e.g., "French", "Spanish")
     * @return string Language code (e.g., "fr", "es")
     */
    public function getLanguageCode(string $languageName): string
    {
        $languageMap = [
            'English' => 'en',
            'French' => 'fr',
            'Spanish' => 'es',
            'German' => 'de',
            'Italian' => 'it',
            'Portuguese' => 'pt',
            'Russian' => 'ru',
            'Chinese' => 'zh',
            'Japanese' => 'ja',
            'Korean' => 'ko',
            'Arabic' => 'ar',
            'Hindi' => 'hi',
            'Dutch' => 'nl',
            'Swedish' => 'sv',
            'Norwegian' => 'no',
            'Danish' => 'da',
            'Finnish' => 'fi',
            'Polish' => 'pl',
            'Turkish' => 'tr',
            'Greek' => 'el',
            'Hebrew' => 'he',
            'Thai' => 'th',
            'Vietnamese' => 'vi',
            'Indonesian' => 'id',
            'Malay' => 'ms',
            'Hungarian' => 'hu',
            'Czech' => 'cs',
            'Slovak' => 'sk',
            'Ukrainian' => 'uk',
            'Romanian' => 'ro',
            'Bulgarian' => 'bg',
            'Croatian' => 'hr',
            'Serbian' => 'sr',
            'Lithuanian' => 'lt',
            'Latvian' => 'lv',
            'Estonian' => 'et',
            'Slovenian' => 'sl',
        ];

        return $languageMap[$languageName] ?? strtolower(substr($languageName, 0, 2));
    }
}
