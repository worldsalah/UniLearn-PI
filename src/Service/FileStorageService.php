<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FileStorageService
{
    private string $uploadDir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->uploadDir = $params->get('kernel.project_dir') . '/public/uploads/courses';
        
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * Stores an uploaded file or a staged file and returns the relative path.
     */
    public function storeFile(string $currentPath, string $originalFilename): string
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $safeFileName = uniqid('course_', true) . '.' . $extension;
        $targetPath = $this->uploadDir . DIRECTORY_SEPARATOR . $safeFileName;

        if (!copy($currentPath, $targetPath)) {
            throw new \Exception("Could not move file to $targetPath");
        }

        // Return the relative public path
        return '/uploads/courses/' . $safeFileName;
    }
}
