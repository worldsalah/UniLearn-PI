<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Quiz;
use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/quiz')]
class QuizController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[Route('/add', name: 'quiz_add', methods: ['POST'])]
    public function addQuiz(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Debug: Log received data
            error_log('Received quiz data: ' . print_r($data, true));

            // Validate required fields
            if (!isset($data['title']) || !isset($data['course_id'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz title and course ID are required',
                    'received_data' => $data
                ], Response::HTTP_BAD_REQUEST);
            }

            // Get course
            $course = $this->entityManager->getRepository(Course::class)->find($data['course_id']);
            if (!$course) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Course not found',
                    'course_id' => $data['course_id']
                ], Response::HTTP_NOT_FOUND);
            }

            // Create quiz
            $quiz = new Quiz();
            $quiz->setTitle($data['title']);
            $quiz->setCourse($course);

            // Debug: Log quiz object before validation
            error_log('Quiz object before validation: ' . print_r([
                'title' => $quiz->getTitle(),
                'course_id' => $quiz->getCourse() ? $quiz->getCourse()->getId() : null
            ], true));

            // Validate quiz
            $errors = $this->validator->validate($quiz);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                    error_log('Validation error: ' . $error->getMessage());
                }
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            // Save quiz
            $this->entityManager->persist($quiz);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Quiz created successfully',
                'quiz' => [
                    'id' => $quiz->getId(),
                    'title' => $quiz->getTitle(),
                    'course_id' => $course->getId()
                ]
            ]);

        } catch (\Exception $e) {
            error_log('Exception in addQuiz: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/question/add', name: 'question_add', methods: ['POST'])]
    public function addQuestion(Request $request): JsonResponse
    {
        error_log("=== addQuestion called ===");
        
        try {
            $data = json_decode($request->getContent(), true);
            error_log("Received question data: " . print_r($data, true));

            // Validate required fields
            $requiredFields = ['quiz_id', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    error_log("Missing required field: " . $field);
                    return new JsonResponse([
                        'success' => false,
                        'message' => "Field '{$field}' is required and cannot be empty"
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Get quiz
            $quiz = $this->entityManager->getRepository(Quiz::class)->find($data['quiz_id']);
            if (!$quiz) {
                error_log("Quiz not found with ID: " . $data['quiz_id']);
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quiz not found'
                ], Response::HTTP_NOT_FOUND);
            }

            error_log("Found quiz: " . $quiz->getTitle());

            // Create question
            $question = new Question();
            $question->setQuestion($data['question']);
            $question->setOptionA($data['option_a']);
            $question->setOptionB($data['option_b']);
            $question->setOptionC($data['option_c']);
            $question->setOptionD($data['option_d']);
            $question->setCorrectOption($data['correct_option']);
            $question->setQuiz($quiz);

            // Validate question
            $errors = $this->validator->validate($question);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            // Save question
            $this->entityManager->persist($question);
            $this->entityManager->flush();
            
            error_log("Question saved successfully with ID: " . $question->getId());

            return new JsonResponse([
                'success' => true,
                'message' => 'Question added successfully',
                'question' => [
                    'id' => $question->getId(),
                    'text' => $question->getQuestion(),
                    'options' => $question->getOptions(),
                    'correctOption' => $question->getCorrectOption()
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/quiz/question/{id}', name: 'question_get', methods: ['GET'])]
    public function getQuestion(int $id): JsonResponse
    {
        try {
            $question = $this->entityManager->getRepository(Question::class)->find($id);
            if (!$question) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Question not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'success' => true,
                'question' => [
                    'id' => $question->getId(),
                    'text' => $question->getQuestion(),
                    'options' => $question->getOptions(),
                    'correctOption' => $question->getCorrectOption()
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/quiz/question/{id}/edit', name: 'question_edit', methods: ['PUT'])]
    public function editQuestion(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Get question
            $question = $this->entityManager->getRepository(Question::class)->find($id);
            if (!$question) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Question not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Update question fields
            if (isset($data['question'])) {
                $question->setQuestion($data['question']);
            }
            if (isset($data['optionA'])) {
                $question->setOptionA($data['optionA']);
            }
            if (isset($data['optionB'])) {
                $question->setOptionB($data['optionB']);
            }
            if (isset($data['optionC'])) {
                $question->setOptionC($data['optionC']);
            }
            if (isset($data['optionD'])) {
                $question->setOptionD($data['optionD']);
            }
            if (isset($data['correctOption'])) {
                $question->setCorrectOption($data['correctOption']);
            }

            // Validate question
            $errors = $this->validator->validate($question);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            // Save changes
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Question updated successfully',
                'question' => [
                    'id' => $question->getId(),
                    'text' => $question->getQuestion(),
                    'options' => $question->getOptions(),
                    'correctOption' => $question->getCorrectOption()
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/quiz/question/{id}/delete', name: 'question_delete', methods: ['DELETE'])]
    public function deleteQuestion(int $id): JsonResponse
    {
        try {
            $question = $this->entityManager->getRepository(Question::class)->find($id);
            if (!$question) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Question not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($question);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Question deleted successfully'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/courses', name: 'quiz_courses', methods: ['GET'])]
    public function getCourses(): JsonResponse
    {
        try {
            $courses = $this->entityManager->getRepository(Course::class)->findAll();
            
            $courseData = [];
            foreach ($courses as $course) {
                $courseData[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'name' => $course->getNom()
                ];
            }

            return new JsonResponse([
                'success' => true,
                'courses' => $courseData
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
