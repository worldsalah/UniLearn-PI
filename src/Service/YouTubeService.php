<?php

namespace App\Service;

use Google\Client;
use Google\Service\YouTube;

class YouTubeService
{
    private Client $client;
    private YouTube $youtube;
    private string $apiKey;

    public function __construct(
        string $googleYoutubeApiKey
    ) {
        $this->apiKey = $googleYoutubeApiKey;
        
        $this->client = new Client();
        $this->client->setDeveloperKey($this->apiKey);
        $this->client->setApplicationName('Unilearn Education Platform');
        $this->client->setScopes(['https://www.googleapis.com/auth/youtube.readonly']);
        
        $this->youtube = new YouTube($this->client);
    }

    /**
     * Search for educational videos on YouTube
     */
    public function searchEducationalVideos(string $query, int $maxResults = 10): array
    {
        try {
            // Clean and format the search query
            $searchQuery = trim($query);
            
            // Make sure the query is not empty
            if (empty($searchQuery)) {
                return [];
            }
            
            $response = $this->youtube->search->listSearch('snippet', [
                'q' => $searchQuery,
                'maxResults' => $maxResults,
                'type' => 'video',
                'videoDuration' => 'medium',
                'order' => 'relevance'
            ]);

            $videos = [];
            foreach ($response->getItems() as $searchResult) {
                $videoId = $searchResult->getId()->getVideoId();
                $snippet = $searchResult->getSnippet();
                
                $videos[] = [
                    'id' => $videoId,
                    'title' => $snippet->getTitle(),
                    'description' => $snippet->getDescription(),
                    'channelTitle' => $snippet->getChannelTitle(),
                    'publishedAt' => $snippet->getPublishedAt(),
                    'thumbnailUrl' => $snippet->getThumbnails()->getDefault()->getUrl(),
                    'embedUrl' => 'https://www.youtube.com/embed/' . $videoId,
                    'duration' => null // Will be populated in getVideoDetails
                ];
            }

            return $videos;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to search YouTube videos: ' . $e->getMessage());
        }
    }

    /**
     * Get detailed video information including duration
     */
    public function getVideoDetails(string $videoId): array
    {
        try {
            $response = $this->youtube->videos->listVideos('snippet,contentDetails', [
                'id' => $videoId
            ]);

            if (empty($response->getItems())) {
                throw new \RuntimeException('Video not found: ' . $videoId);
            }

            $video = $response->getItems()[0];
            $snippet = $video->getSnippet();
            $contentDetails = $video->getContentDetails();

            return [
                'id' => $videoId,
                'title' => $snippet->getTitle(),
                'description' => $snippet->getDescription(),
                'duration' => $contentDetails->getDuration(),
                'embedUrl' => 'https://www.youtube.com/embed/' . $videoId,
                'thumbnailUrl' => $snippet->getThumbnails()->getHigh()->getUrl() ?? $snippet->getThumbnails()->getDefault()->getUrl(),
                'viewCount' => $contentDetails->getViewCount(),
                'likeCount' => $contentDetails->getLikeCount(),
                'channelTitle' => $snippet->getChannelTitle(),
                'publishedAt' => $snippet->getPublishedAt(),
                'tags' => $snippet->getTags() ?? []
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to get video details: ' . $e->getMessage());
        }
    }

    /**
     * Check if video is embeddable (not private or restricted)
     */
    public function isVideoEmbeddable(string $videoId): bool
    {
        try {
            $response = $this->youtube->videos->listVideos('status', [
                'id' => $videoId
            ]);

            if (!empty($response->getItems())) {
                $status = $response->getItems()[0]->getStatus()->getPrivacyStatus();
                return in_array($status, ['public', 'unlisted'], true);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get popular educational videos for web development
     */
    public function getWebDevelopmentVideos(): array
    {
        $queries = [
            'HTML CSS tutorial',
            'JavaScript fundamentals',
            'web development course',
            'CSS examples',
            'responsive design'
        ];

        $allVideos = [];
        foreach ($queries as $query) {
            try {
                $videos = $this->searchEducationalVideos($query, 5);
                $allVideos = array_merge($allVideos, $videos);
            } catch (\Exception $e) {
                // Continue with other queries if one fails
                continue;
            }
        }

        // Remove duplicates and limit results
        $uniqueVideos = [];
        $seenIds = [];
        
        foreach ($allVideos as $video) {
            if (!in_array($video['id'], $seenIds) && count($uniqueVideos) < 20) {
                $uniqueVideos[] = $video;
                $seenIds[] = $video['id'];
            }
        }

        return array_slice($uniqueVideos, 0, 10);
    }

    /**
     * Format duration for display (ISO 8601 to readable format)
     */
    public function formatDuration(string $duration): string
    {
        try {
            $start = new \DateTime('@1970-01-01');
            $start->add(new \DateInterval($duration));
            
            $hours = $start->format('H');
            $minutes = $start->format('i');
            $seconds = $start->format('s');
            
            if ($hours > 0) {
                return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
            } else {
                return sprintf('%02d:%02d', $minutes, $seconds);
            }
        } catch (\Exception $e) {
            return '0:00';
        }
    }
}
