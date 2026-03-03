<?php

namespace App\Tests\Service;

use App\Entity\Certificate;
use App\Entity\User;
use App\Entity\Course;
use App\Service\CertificateManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour CertificateManager
 * Règles métier validées:
 * 1. L'utilisateur est obligatoire
 * 2. Le cours est obligatoire
 * 3. Le nom du fichier est obligatoire
 */
class CertificateManagerTest extends TestCase
{
    public function testValidCertificate(): void
    {
        $user = new User();
        $user->setFullName('Test User');
        
        $course = new Course();
        $course->setTitle('Test Course');
        
        $certificate = new Certificate();
        $certificate->setUser($user);
        $certificate->setCourse($course);
        $certificate->setFilename('certificate_123.pdf');
        
        $manager = new CertificateManager();
        $this->assertTrue($manager->validate($certificate));
    }

    public function testCertificateWithoutUser(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'utilisateur est obligatoire');
        
        $course = new Course();
        $course->setTitle('Test Course');
        
        $certificate = new Certificate();
        $certificate->setCourse($course);
        $certificate->setFilename('certificate_123.pdf');
        
        $manager = new CertificateManager();
        $manager->validate($certificate);
    }

    public function testCertificateWithoutCourse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le cours est obligatoire');
        
        $user = new User();
        $user->setFullName('Test User');
        
        $certificate = new Certificate();
        $certificate->setUser($user);
        $certificate->setFilename('certificate_123.pdf');
        
        $manager = new CertificateManager();
        $manager->validate($certificate);
    }

    public function testCertificateWithoutFilename(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du fichier est obligatoire');
        
        $user = new User();
        $user->setFullName('Test User');
        
        $course = new Course();
        $course->setTitle('Test Course');
        
        $certificate = new Certificate();
        $certificate->setUser($user);
        $certificate->setCourse($course);
        
        $manager = new CertificateManager();
        $manager->validate($certificate);
    }

    public function testGenerateCertificateNumber(): void
    {
        $manager = new CertificateManager();
        $number = $manager->generateCertificateNumber();
        
        $this->assertStringStartsWith('CERT-', $number);
        $this->assertMatchesRegularExpression('/CERT-[A-Z0-9]{8}-\d{4}/', $number);
    }
}
