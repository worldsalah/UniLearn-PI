<?php

namespace App\Controller;

use App\Service\AiAssistantService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class AiChatController extends AbstractController
{
    public function __construct(
        private AiAssistantService $aiAssistant,
    ) {
    }

    #[Route('/profile/ai-chat', name: 'app_ai_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['message']) || empty(trim($data['message']))) {
            return $this->json(['error' => 'Message is required'], 400);
        }

        $user = $this->getUser();
        $message = trim($data['message']);
        $history = $data['history'] ?? [];
        $mode = $data['mode'] ?? 'chat'; // 'chat' or 'bio'

        if ($mode === 'bio') {
            // Generate structured bio suggestions
            $suggestions = $this->aiAssistant->generateBioSuggestions(
                $message,
                $user->getFullName()
            );

            return $this->json([
                'type' => 'suggestions',
                'suggestions' => $suggestions,
            ]);
        }

        // General chat mode
        $response = $this->aiAssistant->chat($message, $history);

        return $this->json([
            'type' => 'message',
            'content' => $response,
        ]);
    }
}
