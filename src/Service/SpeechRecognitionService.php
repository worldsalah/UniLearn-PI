<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpeechRecognitionService
{
    private HttpClientInterface $httpClient;
    private string $groqApiKey;

    public function __construct(HttpClientInterface $httpClient, string $groqApiKey = null)
    {
        $this->httpClient = $httpClient;
        // Use Groq API key (free tier available) - fallback to env variable
        $this->groqApiKey = $groqApiKey ?? $_ENV['GROQ_API_KEY'] ?? null;
    }

    /**
     * Transcribe audio using Groq's FREE Whisper API
     */
    public function transcribeAudio(string $audioBase64, string $language = 'en'): array
    {
        if (empty($this->groqApiKey)) {
            return [
                'success' => false,
                'error' => 'Groq API key not configured. Get a free key at console.groq.com'
            ];
        }

        try {
            // Decode base64 audio
            $audioData = base64_decode($audioBase64, true);
            if ($audioData === false) {
                return [
                    'success' => false,
                    'error' => 'Invalid base64 audio data'
                ];
            }
            
            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'audio_') . '.webm';
            file_put_contents($tempFile, $audioData);

            // Read file for multipart upload
            $fileContent = file_get_contents($tempFile);
            if ($fileContent === false) {
                @unlink($tempFile);
                return [
                    'success' => false,
                    'error' => 'Failed to read audio file'
                ];
            }
            
            // Call Groq Whisper API (FREE!)
            $url = 'https://api.groq.com/openai/v1/audio/transcriptions';
            
            $boundary = '----WebKitFormBoundary' . md5((string) time());
            $body = $this->buildMultipartBody($boundary, $fileContent, $language);
            
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                ],
                'body' => $body,
                'timeout' => 60
            ]);

            // Cleanup temp file
            @unlink($tempFile);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode === 200 && isset($data['text'])) {
                return [
                    'success' => true,
                    'text' => $data['text'],
                    'error' => null
                ];
            }

            return [
                'success' => false,
                'error' => $data['error']['message'] ?? 'Transcription failed'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build multipart form body for file upload
     */
    private function buildMultipartBody(string $boundary, string $fileContent, string $language): string
    {
        $body = '';
        
        // File field
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="file"; filename="audio.webm"' . "\r\n";
        $body .= 'Content-Type: audio/webm' . "\r\n\r\n";
        $body .= $fileContent . "\r\n";
        
        // Model field (whisper-large-v3 is free on Groq)
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="model"' . "\r\n\r\n";
        $body .= 'whisper-large-v3' . "\r\n";
        
        // Language field (optional but helps accuracy)
        if ($language !== '') {
            $body .= '--' . $boundary . "\r\n";
            $body .= 'Content-Disposition: form-data; name="language"' . "\r\n\r\n";
            $body .= $language . "\r\n";
        }
        
        $body .= '--' . $boundary . '--' . "\r\n";
        
        return $body;
    }

    /**
     * Check if Groq API is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->groqApiKey);
    }
}
