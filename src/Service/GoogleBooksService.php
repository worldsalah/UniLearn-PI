<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Autowire]
class GoogleBooksService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        // Direct environment variable access
        $this->apiKey = $_ENV['GOOGLE_BOOKS_API_KEY'] ?? '';
    }

    /**
     * Search for books using Google Books API
     */
    public function searchBooks(string $query): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/books/v1/volumes', [
                'query' => [
                    'q' => $query,
                    'maxResults' => 3,
                    'key' => $this->apiKey,
                    'printType' => 'books',
                    'orderBy' => 'relevance'
                ]
            ]);

            $data = $response->toArray();
            
            if (!isset($data['items']) || empty($data['items'])) {
                return [];
            }

            $books = [];
            foreach ($data['items'] as $item) {
                $volumeInfo = $item['volumeInfo'] ?? [];
                $bookInfo = $item['volumeInfo'] ?? [];
                
                $books[] = [
                    'title' => $volumeInfo['title'] ?? 'Unknown Title',
                    'authors' => $this->formatAuthors($volumeInfo['authors'] ?? []),
                    'description' => $this->truncateDescription($volumeInfo['description'] ?? ''),
                    'thumbnail' => $this->getThumbnail($volumeInfo['imageLinks'] ?? []),
                    'previewLink' => $volumeInfo['previewLink'] ?? null,
                    'infoLink' => $volumeInfo['infoLink'] ?? null,
                    'publishedDate' => $volumeInfo['publishedDate'] ?? null,
                    'pageCount' => $volumeInfo['pageCount'] ?? null,
                    'categories' => $volumeInfo['categories'] ?? [],
                    'averageRating' => $volumeInfo['averageRating'] ?? null,
                    'ratingsCount' => $volumeInfo['ratingsCount'] ?? null
                ];
            }

            return $books;

        } catch (\Exception $e) {
            // Log error and return empty array
            error_log('Google Books API Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format authors array to string
     */
    private function formatAuthors(array $authors): string
    {
        if (empty($authors)) {
            return 'Unknown Author';
        }

        $authorNames = [];
        foreach ($authors as $author) {
            $authorNames[] = $author;
        }

        return implode(', ', $authorNames);
    }

    /**
     * Truncate description to reasonable length
     */
    private function truncateDescription(string $description): string
    {
        if (empty($description)) {
            return 'No description available.';
        }

        // Remove HTML tags and limit to 200 characters
        $cleanDescription = strip_tags($description);
        return strlen($cleanDescription) > 200 
            ? substr($cleanDescription, 0, 197) . '...' 
            : $cleanDescription;
    }

    /**
     * Get thumbnail URL with fallback
     */
    private function getThumbnail(array $imageLinks): ?string
    {
        // Try different thumbnail sizes in order of preference
        $thumbnailTypes = ['thumbnail', 'smallThumbnail', 'mediumThumbnail'];
        
        foreach ($thumbnailTypes as $type) {
            if (isset($imageLinks[$type])) {
                return $imageLinks[$type];
            }
        }

        return null;
    }
}
