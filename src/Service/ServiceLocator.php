<?php

namespace App\Service;

class ServiceLocator
{
    private YouTubeService $youTubeService;

    public function __construct(
        YouTubeService $youTubeService,
    ) {
        $this->youTubeService = $youTubeService;
    }

    public function getYouTubeService(): YouTubeService
    {
        return $this->youTubeService;
    }
}
