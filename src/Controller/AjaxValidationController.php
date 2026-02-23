<?php

namespace App\Controller;

use App\Form\RegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AjaxValidationController extends AbstractController
{
    #[Route('/api/validate-registration', name: 'api_validate_registration', methods: ['POST'])]
    public function validateRegistration(Request $request): JsonResponse
    {
        $form = $this->createForm(RegistrationType::class);
        $form->handleRequest($request);

        $data = json_decode($request->getContent(), true);

        if (null !== $data && false !== $data) {
            // Submit form with data for validation
            $form->submit($data);

            $errors = [];
            if (!$form->isValid()) {
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = [
                        'field' => $error->getOrigin()?->getName(),
                        'message' => $error->getMessage(),
                    ];
                }
            }

            return new JsonResponse([
                'valid' => $form->isValid(),
                'errors' => $errors,
            ]);
        }

        return new JsonResponse(['valid' => false, 'errors' => []]);
    }

    #[Route('/api/validate-login', name: 'api_validate_login', methods: ['POST'])]
    public function validateLogin(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (null === $data || false === $data) {
            return new JsonResponse(['valid' => false, 'errors' => []]);
        }

        $errors = [];
        $isValid = true;

        // Validate email
        if (empty($data['email'])) {
            $errors[] = ['field' => 'email', 'message' => 'L\'adresse email est obligatoire.'];
            $isValid = false;
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['field' => 'email', 'message' => 'L\'email n\'est pas une adresse email valide.'];
            $isValid = false;
        }

        // Validate password
        if (empty($data['password'])) {
            $errors[] = ['field' => 'password', 'message' => 'Le mot de passe est obligatoire.'];
            $isValid = false;
        }

        return new JsonResponse([
            'valid' => $isValid,
            'errors' => $errors,
        ]);
    }
}
