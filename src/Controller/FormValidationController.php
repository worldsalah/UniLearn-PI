<?php

namespace App\Controller;

use App\Form\RegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FormValidationController extends AbstractController
{
    #[Route('/api/validate-form', name: 'api_validate_form', methods: ['POST'])]
    public function validateForm(Request $request): JsonResponse
    {
        $formType = $request->request->get('form_type');
        
        if ($formType === 'registration') {
            $form = $this->createForm(RegistrationType::class);
            
            // Simuler la soumission pour validation
            $formData = [
                'fullName' => $request->request->get('fullName'),
                'email' => $request->request->get('email'),
                'password' => [
                    'first' => $request->request->get('password_first'),
                    'second' => $request->request->get('password_second')
                ]
            ];
            
            $form->submit($formData);
            
            $errors = [];
            if (!$form->isValid()) {
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = [
                        'field' => $error->getOrigin()?->getName(),
                        'message' => $error->getMessage()
                    ];
                }
            }

            return new JsonResponse([
                'valid' => $form->isValid(),
                'errors' => $errors
            ]);
        }
        
        return new JsonResponse(['valid' => false, 'errors' => []]);
    }
}
