<?php

namespace App\Service;

use App\Entity\Certificate;
use App\Entity\User;

/**
 * Service métier pour la gestion des certificats
 * Règles métier:
 * 1. L'utilisateur est obligatoire
 * 2. Le cours est obligatoire
 * 3. Le certificat ne peut être délivré qu'une seule fois par cours
 */
class CertificateManager
{
    public function validate(Certificate $certificate): bool
    {
        if ($certificate->getUser() === null) {
            throw new \InvalidArgumentException('L\'utilisateur est obligatoire');
        }
        
        if ($certificate->getCourse() === null) {
            throw new \InvalidArgumentException('Le cours est obligatoire');
        }
        
        if (empty($certificate->getFilename())) {
            throw new \InvalidArgumentException('Le nom du fichier est obligatoire');
        }
        
        return true;
    }
    
    public function generateCertificateNumber(): string
    {
        return 'CERT-' . strtoupper(substr(uniqid(), -8)) . '-' . date('Y');
    }
}
