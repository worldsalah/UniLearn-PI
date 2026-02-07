<?php

namespace App\Controller;

use App\Service\YouTubeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class YouTubeController extends AbstractController
{
    private YouTubeService $youTubeService;

    public function __construct(YouTubeService $youTubeService)
    {
        $this->youTubeService = $youTubeService;
    }

    /**
     * Video search interface page
     */
    #[Route('/instructor/video-search', name: 'instructor_video_search', methods: ['GET'])]
    public function videoSearchInterface(): Response
    {
        return $this->render('instructor/video-search.html.twig');
    }

    /**
     * Search for YouTube videos
     */
    #[Route('/api/youtube/search', name: 'api_youtube_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $maxResults = $request->query->get('maxResults', 10);

        if (empty($query)) {
            return new JsonResponse(['error' => 'Query parameter is required'], 400);
        }

        try {
            $videos = $this->youTubeService->searchEducationalVideos($query, $maxResults);
            
            return new JsonResponse([
                'success' => true,
                'videos' => $videos,
                'total' => count($videos)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to search videos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get video details
     */
    #[Route('/api/youtube/video/{videoId}', name: 'api_youtube_video_details', methods: ['GET'])]
    public function getVideoDetails(string $videoId): JsonResponse
    {
        try {
            $videoDetails = $this->youTubeService->getVideoDetails($videoId);
            
            return new JsonResponse([
                'success' => true,
                'video' => $videoDetails
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to get video details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get web development videos
     */
    #[Route('/api/youtube/web-dev-videos', name: 'api_youtube_web_dev', methods: ['GET'])]
    public function getWebDevelopmentVideos(): JsonResponse
    {
        try {
            $videos = $this->youTubeService->getWebDevelopmentVideos();
            
            return new JsonResponse([
                'success' => true,
                'videos' => $videos
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to get web development videos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if video can be embedded
     */
    #[Route('/api/youtube/check/{videoId}', name: 'api_youtube_check', methods: ['GET'])]
    public function checkVideoEmbeddable(string $videoId): JsonResponse
    {
        try {
            $isEmbeddable = $this->youTubeService->isVideoEmbeddable($videoId);
            
            return new JsonResponse([
                'success' => true,
                'embeddable' => $isEmbeddable
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to check video: ' . $e->getMessage()
            ], 500);
        }
    }
}
