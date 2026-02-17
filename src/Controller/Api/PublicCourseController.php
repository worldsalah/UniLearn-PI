<?php

namespace App\Controller\Api;

use App\Enum\CourseStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class PublicCourseController extends AbstractController
{
    #[Route('/api/public/courses/transitions', name: 'api_public_course_transitions')]
    public function getTransitions(): JsonResponse
    {
        return new JsonResponse([
            'transitions' => CourseStatus::getAllowedTransitions(),
            'statuses' => array_map(function ($status) {
                return [
                    'value' => $status->value,
                    'label' => $status->getLabel(),
                    'description' => $status->getDescription(),
                    'is_editable' => $status->isEditable(),
                    'is_visible_to_students' => $status->isVisibleToStudents(),
                    'required_role' => $status->getRequiredRole()
                ];
            }, CourseStatus::cases())
        ]);
    }

    #[Route('/api/public/system/status', name: 'api_public_system_status')]
    public function getSystemStatus(): JsonResponse
    {
        return new JsonResponse([
            'system' => 'Course Lifecycle Management System',
            'version' => '1.0.0',
            'status' => 'operational',
            'features' => [
                'state_machine' => true,
                'audit_logging' => true,
                'version_control' => true,
                'role_based_access' => true,
                'event_notifications' => true
            ],
            'available_statuses' => array_map(function ($status) {
                return [
                    'value' => $status->value,
                    'label' => $status->getLabel()
                ];
            }, CourseStatus::cases())
        ]);
    }
}
