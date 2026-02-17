<?php

require_once 'vendor/autoload.php';

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

// Get the hashed password
$password = 'malek123+';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Hashed password: " . $hash . "\n";
echo "Hash length: " . strlen($hash) . "\n";

// Test the hash
if (password_verify($password, $hash)) {
    echo "Password verification: SUCCESS\n";
} else {
    echo "Password verification: FAILED\n";
}
