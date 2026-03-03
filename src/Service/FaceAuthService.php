<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FaceAuthService
{
    private EntityManagerInterface $entityManager;
    private string $pythonScriptsPath;
    private string $faceDataPath;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params
    ) {
        $this->entityManager = $entityManager;
        $projectDir = is_string($params->get('kernel.project_dir')) ? $params->get('kernel.project_dir') : '';
        $this->pythonScriptsPath = $projectDir . '/scripts/face_auth';
        $this->faceDataPath = $projectDir . '/var/face_data';
        
        // Ensure directories exist
        if (!is_dir($this->faceDataPath)) {
            mkdir($this->faceDataPath, 0755, true);
        }
    }

    /**
     * Detect face in image and return face encoding
     */
    public function detectAndEncodeFace(string $imageBase64): ?array
    {
        $debugFile = 'C:/xampp/htdocs/UniLearn-PI-main/var/log/faceauth_debug.log';
        file_put_contents($debugFile, "\n=== " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
        file_put_contents($debugFile, "Starting face detection\n", FILE_APPEND);
        
        // Save temporary image
        $tempFile = tempnam(sys_get_temp_dir(), 'face_') . '.jpg';
        $imageData = base64_decode((string) preg_replace('#^data:image/\w+;base64,#i', '', $imageBase64), true);
        if ($imageData === false) {
            file_put_contents($debugFile, "Failed to decode base64 image\n", FILE_APPEND);
            return null;
        }
        file_put_contents($tempFile, $imageData);
        file_put_contents($debugFile, "Image saved to $tempFile (" . strlen($imageData) . " bytes)\n", FILE_APPEND);

        // Run Python script for face detection and encoding
        $scriptPath = $this->pythonScriptsPath . '/encode_face.py';
        file_put_contents($debugFile, "Script path: $scriptPath (exists: " . (file_exists($scriptPath) ? 'yes' : 'no') . ")\n", FILE_APPEND);
        
        // Use 'py' launcher on Windows, 'python' on Linux/Mac
        $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'py' : 'python';
        file_put_contents($debugFile, "Python command: $pythonCmd\n", FILE_APPEND);
        
        $command = sprintf(
            '%s "%s" "%s" 2>&1',
            $pythonCmd,
            $scriptPath,
            $tempFile
        );
        file_put_contents($debugFile, "Running: $command\n", FILE_APPEND);

        $output = shell_exec($command);
        unlink($tempFile);
        file_put_contents($debugFile, "Python output: $output\n", FILE_APPEND);

        $result = json_decode(is_string($output) ? $output : '', true);
        
        if (!is_array($result) || !($result['success'] ?? false)) {
            file_put_contents($debugFile, "Face detection failed: " . ($result['error'] ?? 'unknown') . "\n", FILE_APPEND);
            return null;
        }

        file_put_contents($debugFile, "Face detected successfully\n", FILE_APPEND);
        return $result['encoding'];
    }

    /**
     * Register face for user
     */
    public function registerFace(User $user, string $imageBase64): bool
    {
        $encoding = $this->detectAndEncodeFace($imageBase64);
        
        if ($encoding === null) {
            return false;
        }

        // Store face encoding
        $user->setFaceEncoding(json_encode($encoding) ?: null);
        $user->setFaceEnabled(true);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Verify face against stored encoding
     */
    public function verifyFace(User $user, string $imageBase64): bool
    {
        $debugFile = 'C:/xampp/htdocs/UniLearn-PI-main/var/log/faceauth_debug.log';
        file_put_contents($debugFile, "\n=== VERIFY FACE ===\n", FILE_APPEND);
        
        $faceEncoding = $user->getFaceEncoding();
        if (!$user->isFaceEnabled() || $faceEncoding === null || $faceEncoding === '') {
            file_put_contents($debugFile, "Face not enabled or no encoding for user\n", FILE_APPEND);
            return false;
        }

        $currentEncoding = $this->detectAndEncodeFace($imageBase64);
        
        if ($currentEncoding === null) {
            file_put_contents($debugFile, "Could not encode current face\n", FILE_APPEND);
            return false;
        }

        $storedEncoding = json_decode($faceEncoding, true);
        file_put_contents($debugFile, "Stored encoding length: " . count($storedEncoding) . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Current encoding length: " . count($currentEncoding) . "\n", FILE_APPEND);
        
        // Write encodings to temporary files to avoid command line length limits
        $tempFile1 = tempnam(sys_get_temp_dir(), 'face_enc1_');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'face_enc2_');
        file_put_contents($tempFile1, json_encode($storedEncoding));
        file_put_contents($tempFile2, json_encode($currentEncoding));
        file_put_contents($debugFile, "Temp files: $tempFile1, $tempFile2\n", FILE_APPEND);
        
        // Run Python script for face comparison using file paths
        $scriptPath = $this->pythonScriptsPath . '/compare_faces.py';
        $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'py' : 'python';
        $command = sprintf(
            '%s "%s" "%s" "%s" 2>&1',
            $pythonCmd,
            $scriptPath,
            $tempFile1,
            $tempFile2
        );
        
        file_put_contents($debugFile, "Running: $command\n", FILE_APPEND);
        $output = shell_exec($command);
        file_put_contents($debugFile, "Python output: $output\n", FILE_APPEND);
        
        // Clean up temp files
        unlink($tempFile1);
        unlink($tempFile2);
        
        $result = json_decode(is_string($output) ? $output : '', true);

        if (!is_array($result) || !($result['success'] ?? false)) {
            file_put_contents($debugFile, "Comparison failed\n", FILE_APPEND);
            return false;
        }

        // Threshold for face match (lower is better match)
        $match = $result['distance'] < 0.6;
        file_put_contents($debugFile, "Match result: " . ($match ? 'yes' : 'no') . " (distance: " . $result['distance'] . ")\n", FILE_APPEND);
        return $match;
    }

    /**
     * Find user by face
     */
    public function findUserByFace(string $imageBase64): ?User
    {
        $debugFile = 'C:/xampp/htdocs/UniLearn-PI-main/var/log/faceauth_debug.log';
        file_put_contents($debugFile, "\n=== FIND USER BY FACE ===\n", FILE_APPEND);
        
        $currentEncoding = $this->detectAndEncodeFace($imageBase64);
        
        if ($currentEncoding === null) {
            file_put_contents($debugFile, "Could not encode current face\n", FILE_APPEND);
            return null;
        }

        // Get all users with face enabled
        $users = $this->entityManager->getRepository(User::class)
            ->findBy(['faceEnabled' => true]);
        
        file_put_contents($debugFile, "Found " . count($users) . " users with face enabled\n", FILE_APPEND);

        $bestMatch = null;
        $bestDistance = PHP_FLOAT_MAX;

        // Write current encoding to temp file once (reused for all comparisons)
        $currentEncFile = tempnam(sys_get_temp_dir(), 'face_curr_');
        file_put_contents($currentEncFile, json_encode($currentEncoding));

        foreach ($users as $user) {
            file_put_contents($debugFile, "Checking user: " . $user->getEmail() . "\n", FILE_APPEND);
            $userFaceEncoding = $user->getFaceEncoding();
            $storedEncoding = $userFaceEncoding !== null ? json_decode($userFaceEncoding, true) : null;
            if (!is_array($storedEncoding)) {
                file_put_contents($debugFile, "  No stored encoding, skipping\n", FILE_APPEND);
                continue;
            }
            file_put_contents($debugFile, "  Stored encoding length: " . count($storedEncoding) . "\n", FILE_APPEND);

            // Write stored encoding to temp file
            $storedEncFile = tempnam(sys_get_temp_dir(), 'face_stored_');
            file_put_contents($storedEncFile, json_encode($storedEncoding));
            file_put_contents($debugFile, "  Temp files: $storedEncFile, $currentEncFile\n", FILE_APPEND);

            $scriptPath = $this->pythonScriptsPath . '/compare_faces.py';
            $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'py' : 'python';
            $command = sprintf(
                '%s "%s" "%s" "%s" 2>&1',
                $pythonCmd,
                $scriptPath,
                $storedEncFile,
                $currentEncFile
            );
            file_put_contents($debugFile, "  Running: $command\n", FILE_APPEND);

            $output = shell_exec($command);
            file_put_contents($debugFile, "  Python output: $output\n", FILE_APPEND);
            $result = json_decode(is_string($output) ? $output : '', true);
            
            // Clean up stored encoding temp file
            unlink($storedEncFile);

            if (is_array($result) && ($result['success'] ?? false) && $result['distance'] < $bestDistance) {
                $bestDistance = $result['distance'];
                $bestMatch = $user;
                file_put_contents($debugFile, "  New best match! Distance: $bestDistance\n", FILE_APPEND);
            }
        }
        
        // Clean up current encoding temp file
        unlink($currentEncFile);

        // Only return if match is good enough
        if ($bestMatch !== null && $bestDistance < 0.6) {
            file_put_contents($debugFile, "Best match: " . $bestMatch->getEmail() . " (distance: $bestDistance)\n", FILE_APPEND);
            return $bestMatch;
        }

        file_put_contents($debugFile, "No match found (best distance: $bestDistance)\n", FILE_APPEND);
        return null;
    }

    /**
     * Check if face authentication is available
     */
    public function isAvailable(): bool
    {
        $scriptPath = $this->pythonScriptsPath . '/encode_face.py';
        $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'py' : 'python';
        
        // Check if script exists and Python command works
        if (!file_exists($scriptPath)) {
            return false;
        }
        
        // Test Python is working
        $testCmd = $pythonCmd . ' --version 2>&1';
        $output = shell_exec($testCmd);
        
        return is_string($output) && strpos($output, 'Python') !== false;
    }
}
