<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\TranslationApiService;
use Google\Cloud\DocumentAI\V1\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\RawDocument;
use Google\Cloud\DocumentAI\V1\ProcessRequest;
use Google\Cloud\DocumentAI\V1\ProcessOptions;

class TranslationController extends AbstractController
{
    private TranslationApiService $translationService;

    public function __construct(TranslationApiService $translationService)
    {
        $this->translationService = $translationService;
    }

    #[Route('/translate/text', name: 'app_translate_text', methods: ['POST'])]
    public function translateText(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $text = $data['text'] ?? '';
            $targetLanguage = $data['targetLanguage'] ?? 'en';
            $sourceLanguage = $data['sourceLanguage'] ?? null;

            if (empty($text)) {
                return new JsonResponse(['error' => 'Text is required'], 400);
            }

            if (empty($targetLanguage)) {
                return new JsonResponse(['error' => 'Target language is required'], 400);
            }

            $result = $this->translationService->translateText($text, $targetLanguage, $sourceLanguage);

            return new JsonResponse($result);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Translation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/translate/batch', name: 'app_translate_batch', methods: ['POST'])]
    public function translateBatch(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $texts = $data['texts'] ?? [];
            $targetLanguage = $data['targetLanguage'] ?? 'en';
            $sourceLanguage = $data['sourceLanguage'] ?? null;

            if (empty($texts) || !is_array($texts)) {
                return new JsonResponse(['error' => 'Texts array is required'], 400);
            }

            if (empty($targetLanguage)) {
                return new JsonResponse(['error' => 'Target language is required'], 400);
            }

            $result = $this->translationService->translateBatch($texts, $targetLanguage, $sourceLanguage);

            return new JsonResponse($result);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Batch translation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/translate/question-options', name: 'app_translate_question_options', methods: ['POST'])]
    public function translateQuestionOptions(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $options = $data['options'] ?? [];
            $targetLanguage = $data['targetLanguage'] ?? 'en';
            $sourceLanguage = $data['sourceLanguage'] ?? null;

            if (empty($options) || !is_array($options)) {
                return new JsonResponse(['error' => 'Options array is required'], 400);
            }

            if (empty($targetLanguage)) {
                return new JsonResponse(['error' => 'Target language is required'], 400);
            }

            $result = $this->translationService->translateQuestionOptions($options, $targetLanguage, $sourceLanguage);

            return new JsonResponse($result);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Question options translation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/translate/detect-language', name: 'app_translate_detect_language', methods: ['POST'])]
    public function detectLanguage(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $text = $data['text'] ?? '';

            if (empty($text)) {
                return new JsonResponse(['error' => 'Text is required'], 400);
            }

            $result = $this->translationService->detectLanguage($text);

            return new JsonResponse($result);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Language detection failed: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/translate/supported-languages', name: 'app_translate_supported_languages', methods: ['GET'])]
    public function getSupportedLanguages(): JsonResponse
    {
        try {
            $result = $this->translationService->getSupportedLanguages();

            return new JsonResponse($result);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch supported languages: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/translate/language-code', name: 'app_translate_language_code', methods: ['POST'])]
    public function getLanguageCode(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $languageName = $data['languageName'] ?? '';

            if (empty($languageName)) {
                return new JsonResponse(['error' => 'Language name is required'], 400);
            }

            $languageCode = $this->translationService->getLanguageCode($languageName);

            return new JsonResponse([
                'success' => true,
                'languageName' => $languageName,
                'languageCode' => $languageCode
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to get language code: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/translate/document-ai', name: 'app_translate_document_ai', methods: ['POST'])]
    public function translateDocumentAI(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $text = $data['text'] ?? '';
            $questionId = $data['questionId'] ?? null;

            if (empty($text)) {
                return new JsonResponse(['error' => 'Text is required'], 400);
            }

            // Get Google Cloud credentials from environment
            $projectId = $_ENV['GOOGLE_DOCUMENT_AI_PROJECT_ID'] ?? 'unilearn-pi-project';
            $location = $_ENV['GOOGLE_DOCUMENT_AI_LOCATION'] ?? 'us';
            $processorId = $_ENV['GOOGLE_DOCUMENT_AI_PROCESSOR_ID'] ?? 'document-ai-processor-001';
            
            // Set the path to service account credentials
            $projectDir = $this->getParameter('kernel.project_dir');
            $credentialsPath = $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] ?? 
                               (is_string($projectDir) ? $projectDir : '') . '/config/service-account-key.json';

            // Check if credentials file exists
            if (!file_exists($credentialsPath)) {
                return new JsonResponse([
                    'error' => 'Service account credentials not found. Please configure GOOGLE_APPLICATION_CREDENTIALS.',
                    'translatedText' => '[FR] ' . $text . ' (Translation temporarily disabled)'
                ], 500);
            }

            // Initialize Document AI client
            $client = new DocumentProcessorServiceClient([
                'credentials' => $credentialsPath
            ]);

            // Build the processor name
            $processorName = sprintf(
                'projects/%s/locations/%s/processors/%s',
                $projectId,
                $location,
                $processorId
            );

            // Create raw document
            $rawDocument = new RawDocument([
                'content' => $text,
                'mime_type' => 'text/plain'
            ]);

            // Create process request
            $request = new ProcessRequest([
                'name' => $processorName,
                'raw_document' => $rawDocument,
                'skip_human_review' => true,
                'process_options' => new ProcessOptions([
                    'ocr_config' => null // Disable OCR for text input
                ])
            ]);

            // Process the document
            $response = $client->processDocument($request);
            $document = $response->getDocument();

            // Extract translated text
            $translatedText = $document->getText();

            // If no translation was performed, return original text with prefix
            if (empty($translatedText) || $translatedText === $text) {
                $translatedText = '[FR] ' . $text;
            }

            return new JsonResponse([
                'success' => true,
                'translatedText' => $translatedText,
                'questionId' => $questionId,
                'originalText' => $text
            ]);

        } catch (\Exception $e) {
            // Return error with fallback translation
            return new JsonResponse([
                'error' => 'Translation service temporarily unavailable: ' . $e->getMessage(),
                'translatedText' => '[FR] ' . $text,
                'questionId' => $questionId,
                'originalText' => $text
            ], 500);
        }
    }
}
