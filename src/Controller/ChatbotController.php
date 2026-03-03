<?php

namespace App\Controller;

use App\Service\GeminiChatbotService;
use App\Service\SpeechRecognitionService;
use App\Service\ChatbotActionExecutor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

class ChatbotController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/chatbot/message', name: 'app_chatbot_message', methods: ['POST'])]
    public function sendMessage(Request $request, GeminiChatbotService $chatbotService, ChatbotActionExecutor $actionExecutor): JsonResponse
    {
        // Handle both JSON and FormData requests
        $message = $request->request->get('message', '');
        $contextJson = $request->request->get('context', '[]');
        $language = $request->request->get('language', 'en');
        $personality = $request->request->get('personality', 'friendly');
        $attachmentInfoJson = $request->request->get('attachmentInfo', '[]');
        
        $context = json_decode((string) $contextJson, true) ?: [];
        $attachmentInfo = json_decode((string) $attachmentInfoJson, true) ?: [];
        
        // Handle uploaded files
        $attachments = [];
        $uploadedFiles = $request->files->get('attachments', []);
        foreach ($uploadedFiles as $file) {
            if ($file !== null && $file->isValid()) {
                $attachments[] = [
                    'file' => $file,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType()
                ];
            }
        }

        if (empty($message) && empty($attachments)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Message or attachment is required'
            ], 400);
        }

        // Get current user if logged in
        $user = $this->security->getUser();
        $userId = $user !== null ? $user->getId() : null;

        $response = $chatbotService->sendMessage((string) $message, $context, (string) $language, $userId, $attachments);

        // Handle AI agent actions
        if (isset($response['action']) && $response['action']) {
            $action = $response['action'];
            
            // Check if this is an executable action (not just navigation)
            if ($this->isExecutableAction($action['type'])) {
                // Execute the action
                $actionResult = $actionExecutor->execute(
                    $action['type'],
                    $action['params'] ?? [],
                    $user instanceof \App\Entity\User ? $user : null
                );
                
                // Add action result to response
                $response['action_result'] = $actionResult;
                
                // If action was successful, update the message
                if ($actionResult['success']) {
                    $response['message'] = $actionResult['message'];
                } else {
                    $response['message'] = $actionResult['error'];
                }
            }
        }

        return new JsonResponse($response);
    }

    /**
     * Check if an action type should be executed server-side
     */
    private function isExecutableAction(string $actionType): bool
    {
        $executableActions = [
            'update_profile',
            'change_password',
            'enroll_course',
            'unenroll_course',
            'add_favorite',
            'remove_favorite',
            'update_preferences',
            'search_content',
            'get_recommendations'
        ];
        
        return in_array($actionType, $executableActions, true);
    }

    #[Route('/chatbot/execute', name: 'app_chatbot_execute', methods: ['POST'])]
    public function executeAction(Request $request, ChatbotActionExecutor $actionExecutor): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $actionType = $data['action'] ?? '';
        $params = $data['params'] ?? [];
        
        if (empty($actionType)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Action type is required'
            ], 400);
        }

        $user = $this->security->getUser();
        
        $result = $actionExecutor->execute($actionType, $params, $user instanceof \App\Entity\User ? $user : null);
        
        return new JsonResponse($result);
    }

    #[Route('/chatbot/actions', name: 'app_chatbot_actions', methods: ['GET'])]
    public function getAvailableActions(ChatbotActionExecutor $actionExecutor): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'actions' => $actionExecutor->getAvailableActions()
        ]);
    }

    #[Route('/chatbot/transcribe', name: 'app_chatbot_transcribe', methods: ['POST'])]
    public function transcribeAudio(Request $request, SpeechRecognitionService $speechService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $audioBase64 = $data['audio'] ?? '';
        $language = $data['language'] ?? 'en';

        if (empty($audioBase64)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Audio data is required'
            ], 400);
        }

        // Remove data URL prefix if present
        if (strpos($audioBase64, 'data:') === 0) {
            $commaPos = strpos($audioBase64, ',');
            if ($commaPos !== false) {
                $audioBase64 = substr($audioBase64, $commaPos + 1);
            }
        }

        $result = $speechService->transcribeAudio($audioBase64, $language);

        return new JsonResponse($result);
    }

    #[Route('/chatbot/recommendations', name: 'app_chatbot_recommendations', methods: ['GET'])]
    public function getRecommendations(GeminiChatbotService $chatbotService): JsonResponse
    {
        $user = $this->security->getUser();
        
        if ($user === null) {
            return new JsonResponse([
                'success' => false,
                'error' => 'User not logged in'
            ], 401);
        }

        $recommendations = $chatbotService->getPersonalizedRecommendations($user->getId());

        return new JsonResponse($recommendations);
    }

    #[Route('/chatbot/clear', name: 'app_chatbot_clear', methods: ['POST'])]
    public function clearConversation(): JsonResponse
    {
        // This is handled client-side, but we can add server-side logging here
        return new JsonResponse([
            'success' => true,
            'message' => 'Conversation cleared'
        ]);
    }
}
