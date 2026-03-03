<?php

namespace App\Controller\Admin;

use App\Repository\QuizRepository;
use App\Repository\QuestionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin')]
// #[IsGranted('ROLE_ADMIN')]
class QuizController extends AbstractController
{
    #[Route('/quizzes', name: 'app_admin_quizzes', methods: ['GET'])]
    public function index(
        Request $request,
        QuizRepository $quizRepository,
        QuestionRepository $questionRepository
    ): Response {
        // Get search and filter parameters
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $sortBy = $request->query->get('sort', 'createdAt');
        $sortOrder = $request->query->get('order', 'desc');
        
        // Build query
        $queryBuilder = $quizRepository->createQueryBuilder('q')
            ->leftJoin('q.course', 'c')
            ->addSelect('c')
            ->leftJoin('q.questions', 'quest')
            ->addSelect('quest');
        
        // Apply search filter
        if (!empty($search)) {
            $queryBuilder->andWhere('q.title LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        // Apply status filter
        if ($status === 'active') {
            $queryBuilder->andWhere('q.isActive = :active')
                ->setParameter('active', true);
        } elseif ($status === 'inactive') {
            $queryBuilder->andWhere('q.isActive = :active')
                ->setParameter('active', false);
        }
        
        // Apply sorting
        switch ($sortBy) {
            case 'title':
                $queryBuilder->orderBy('q.title', (string) $sortOrder);
                break;
            case 'questions':
                $queryBuilder->orderBy('COUNT(quest.id)', (string) $sortOrder);
                break;
            default:
                $queryBuilder->orderBy('q.createdAt', (string) $sortOrder);
                break;
        }
        
        // Get quizzes
        $quizzes = $queryBuilder->getQuery()->getResult();
        
        // Get all questions for each quiz
        $allQuestions = [];
        foreach ($quizzes as $quiz) {
            $questions = $questionRepository->findBy(['quiz' => $quiz]);
            $allQuestions[] = ['questions' => $questions];
        }
        
        // Handle AJAX request
        if ($request->query->get('ajax') !== null) {
            return $this->json([
                'success' => true,
                'quizzes' => $this->formatQuizzesForAjax($quizzes, $allQuestions)
            ]);
        }
        
        // Create chart data (placeholder for now)
        $scoresChart = new Chart('bar');
        $scoresChart->setData([
            'labels' => ['0-20', '21-40', '41-60', '61-80', '81-100'],
            'datasets' => [[
                'label' => 'Number of Students',
                'data' => [5, 12, 18, 25, 15],
                'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'borderWidth' => 1
            ]]
        ]);
        $scoresChart->setOptions([
            'responsive' => true,
            'scales' => ['y' => ['beginAtZero' => true]]
        ]);
        
        $quizPerformanceChart = new Chart('line');
        $quizPerformanceChart->setData([
            'labels' => ['Quiz 1', 'Quiz 2', 'Quiz 3', 'Quiz 4', 'Quiz 5'],
            'datasets' => [[
                'label' => 'Average Score',
                'data' => [65, 72, 78, 82, 88],
                'borderColor' => 'rgba(75, 192, 192, 1)',
                'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                'tension' => 0.1
            ]]
        ]);
        $quizPerformanceChart->setOptions([
            'responsive' => true,
            'scales' => ['y' => ['beginAtZero' => true, 'max' => 100]]
        ]);
        
        $questionsStatsChart = new Chart('pie');
        $questionsStatsChart->setData([
            'labels' => ['Multiple Choice', 'True/False', 'Short Answer', 'Essay'],
            'datasets' => [[
                'data' => [45, 25, 20, 10],
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 205, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)'
                ],
                'borderColor' => [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                'borderWidth' => 1
            ]]
        ]);
        $questionsStatsChart->setOptions([
            'responsive' => true
        ]);
        
        return $this->render('admin/quiz/index.html.twig', [
            'title' => 'Quizzes Management - Unilearn',
            'quizzes' => $quizzes,
            'allQuestions' => $allQuestions,
            'search' => $search,
            'status' => $status,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'scoresChart' => [
                'type' => 'bar',
                'data' => [
                    'labels' => ['0-20', '21-40', '41-60', '61-80', '81-100'],
                    'datasets' => [[
                        'label' => 'Number of Students',
                        'data' => [5, 12, 18, 25, 15],
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1
                    ]]
                ],
                'options' => [
                    'responsive' => true,
                    'scales' => ['y' => ['beginAtZero' => true]],
                    'plugins' => ['legend' => ['position' => 'top'], 'title' => ['display' => true, 'text' => 'Distribution des scores des quiz']]
                ]
            ],
            'quizPerformanceChart' => [
                'type' => 'line',
                'data' => [
                    'labels' => ['Quiz 1', 'Quiz 2', 'Quiz 3', 'Quiz 4', 'Quiz 5'],
                    'datasets' => [[
                        'label' => 'Score moyen',
                        'data' => [65, 72, 78, 82, 88],
                        'borderColor' => 'rgba(75, 192, 192, 1)',
                        'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                        'tension' => 0.1
                    ]]
                ],
                'options' => [
                    'responsive' => true,
                    'scales' => ['y' => ['beginAtZero' => true, 'max' => 100]],
                    'plugins' => ['legend' => ['position' => 'top'], 'title' => ['display' => true, 'text' => 'Performance par quiz']]
                ]
            ],
            'questionsStatsChart' => [
                'type' => 'pie',
                'data' => [
                    'labels' => ['Multiple Choice', 'True/False', 'Short Answer', 'Essay'],
                    'datasets' => [[
                        'data' => [45, 25, 20, 10],
                        'backgroundColor' => [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 205, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)'
                        ],
                        'borderColor' => [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 205, 86, 1)',
                            'rgba(75, 192, 192, 1)'
                        ],
                        'borderWidth' => 1
                    ]]
                ],
                'options' => [
                    'responsive' => true,
                    'plugins' => ['legend' => ['position' => 'top'], 'title' => ['display' => true, 'text' => 'Distribution des questions par quiz']]
                ]
            ]
        ]);
    }
    
    private function formatQuizzesForAjax($quizzes, $allQuestions): array
    {
        $formattedQuizzes = [];
        
        foreach ($quizzes as $index => $quiz) {
            $formattedQuizzes[] = [
                'id' => $quiz->getId(),
                'title' => $quiz->getTitle(),
                'course' => $quiz->getCourse() ? [
                    'id' => $quiz->getCourse()->getId(),
                    'title' => $quiz->getCourse()->getTitle()
                ] : null,
                'questions' => $allQuestions[$index]['questions'] ?? [],
                'createdAt' => $quiz->getCreatedAt() ? $quiz->getCreatedAt()->format('Y-m-d\TH:i:s') : null,
                'isActive' => $quiz->isActive()
            ];
        }
        
        return $formattedQuizzes;
    }
}
