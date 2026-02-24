<?php

namespace App\Controller;

use App\Entity\QuizStatistics;
use App\Entity\Quiz;
use App\Repository\QuizStatisticsRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin/statistics')]
class QuizStatisticsController extends AbstractController
{
    public function __construct(
        private ChartBuilderInterface $chartBuilder,
        private QuizStatisticsRepository $statsRepository,
        private QuizRepository $quizRepository
    ) {}

    #[Route('/', name: 'app_quiz_statistics')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $quizId = $request->query->get('quiz_id');

        // Récupérer les statistiques avec filtres
        $queryBuilder = $this->statsRepository->createQueryBuilder('qs')
            ->leftJoin('qs.quiz', 'q')
            ->leftJoin('qs.student', 's')
            ->addSelect('q', 's')
            ->orderBy('qs.completedAt', 'DESC');

        if ($startDate) {
            $queryBuilder->andWhere('qs.completedAt >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($startDate));
        }

        if ($endDate) {
            $queryBuilder->andWhere('qs.completedAt <= :endDate')
                ->setParameter('endDate', new \DateTimeImmutable($endDate . ' 23:59:59'));
        }

        if ($quizId) {
            $queryBuilder->andWhere('qs.quiz = :quizId')
                ->setParameter('quizId', $quizId);
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        // Récupérer tous les quizzes pour le filtre
        $quizzes = $this->quizRepository->findAll();

        // Créer les graphiques
        $scoresChart = $this->createScoresChart($startDate, $endDate, $quizId);
        $successDistributionChart = $this->createSuccessDistributionChart($startDate, $endDate, $quizId);
        $difficultQuestionsChart = $this->createDifficultQuestionsChart($startDate, $endDate, $quizId);

        return $this->render('quiz/statistics.html.twig', [
            'pagination' => $pagination,
            'quizzes' => $quizzes,
            'scoresChart' => $scoresChart,
            'successDistributionChart' => $successDistributionChart,
            'difficultQuestionsChart' => $difficultQuestionsChart,
            'currentFilters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'quiz_id' => $quizId
            ]
        ]);
    }

    private function createScoresChart(?string $startDate, ?string $endDate, ?string $quizId): Chart
    {
        $chart = $this->chartBuilder->createChart('scoresChart');

        // Récupérer les données des scores
        $queryBuilder = $this->statsRepository->createQueryBuilder('qs')
            ->select('qs.score as score, COUNT(qs.id) as count')
            ->groupBy('qs.score')
            ->orderBy('qs.score', 'ASC');

        if ($startDate) {
            $queryBuilder->andWhere('qs.completedAt >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($startDate));
        }

        if ($endDate) {
            $queryBuilder->andWhere('qs.completedAt <= :endDate')
                ->setParameter('endDate', new \DateTimeImmutable($endDate . ' 23:59:59'));
        }

        if ($quizId) {
            $queryBuilder->andWhere('qs.quiz = :quizId')
                ->setParameter('quizId', $quizId);
        }

        $results = $queryBuilder->getQuery()->getResult();

        $labels = [];
        $data = [];
        foreach ($results as $result) {
            $labels[] = $result['score'] . '%';
            $data[] = $result['count'];
        }

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nombre d\'étudiants',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.8)',
                    'borderColor' => 'rgba(99, 102, 241, 1)',
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                    'data' => $data,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => 'rgba(99, 102, 241, 1)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'font' => [
                            'size' => 14,
                            'weight' => '600',
                            'family' => 'Inter, system-ui, sans-serif'
                        ],
                        'padding' => 20,
                        'usePointStyle' => true,
                    ]
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Distribution des scores des étudiants',
                    'font' => [
                        'size' => 18,
                        'weight' => '700',
                        'family' => 'Inter, system-ui, sans-serif'
                    ],
                    'padding' => 30
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(17, 24, 39, 0.9)',
                    'titleFont' => [
                        'size' => 14,
                        'weight' => '600'
                    ],
                    'bodyFont' => [
                        'size' => 13
                    ],
                    'padding' => 12,
                    'borderRadius' => 8,
                    'displayColors' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + \': \' + context.parsed.y + \' étudiants\';
                        }'
                    ]
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(156, 163, 175, 0.2)',
                        'drawBorder' => false
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 12,
                            'weight' => '500'
                        ],
                        'color' => '#6b7280',
                        'padding' => 10
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Nombre d\'étudiants',
                        'font' => [
                            'size' => 14,
                            'weight' => '600'
                        ],
                        'color' => '#374151'
                    ]
                ],
                'x' => [
                    'grid' => [
                        'display' => false
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 12,
                            'weight' => '500'
                        ],
                        'color' => '#6b7280',
                        'padding' => 10
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Score (%)',
                        'font' => [
                            'size' => 14,
                            'weight' => '600'
                        ],
                        'color' => '#374151'
                    ]
                ]
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index'
            ],
            'animation' => [
                'duration' => 1000,
                'easing' => 'easeInOutQuart'
            ]
        ]);

        return $chart;
    }

    private function createSuccessDistributionChart(?string $startDate, ?string $endDate, ?string $quizId): Chart
    {
        $chart = $this->chartBuilder->createChart('successDistributionChart');

        // Calculer la distribution de réussite
        $queryBuilder = $this->statsRepository->createQueryBuilder('qs')
            ->select('qs.score as score');

        if ($startDate) {
            $queryBuilder->andWhere('qs.completedAt >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($startDate));
        }

        if ($endDate) {
            $queryBuilder->andWhere('qs.completedAt <= :endDate')
                ->setParameter('endDate', new \DateTimeImmutable($endDate . ' 23:59:59'));
        }

        if ($quizId) {
            $queryBuilder->andWhere('qs.quiz = :quizId')
                ->setParameter('quizId', $quizId);
        }

        $results = $queryBuilder->getQuery()->getResult();

        $excellent = 0; // 90-100%
        $good = 0;     // 75-89%
        $average = 0;  // 60-74%
        $poor = 0;     // 0-59%

        foreach ($results as $result) {
            $score = $result['score'];
            if ($score >= 90) {
                $excellent++;
            } elseif ($score >= 75) {
                $good++;
            } elseif ($score >= 60) {
                $average++;
            } else {
                $poor++;
            }
        }

        $chart->setData([
            'labels' => ['Excellent (90-100%)', 'Bon (75-89%)', 'Moyen (60-74%)', 'Faible (0-59%)'],
            'datasets' => [
                [
                    'label' => 'Distribution de réussite',
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.85)',
                        'rgba(59, 130, 246, 0.85)',
                        'rgba(251, 191, 36, 0.85)',
                        'rgba(239, 68, 68, 0.85)',
                    ],
                    'borderColor' => [
                        'rgba(34, 197, 94, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(251, 191, 36, 1)',
                        'rgba(239, 68, 68, 1)',
                    ],
                    'borderWidth' => 2,
                    'borderRadius' => 12,
                    'data' => [$excellent, $good, $average, $poor],
                    'hoverOffset' => 8,
                    'spacing' => 2,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'font' => [
                            'size' => 13,
                            'weight' => '600',
                            'family' => 'Inter, system-ui, sans-serif'
                        ],
                        'padding' => 20,
                        'usePointStyle' => true,
                        'pointStyleWidth' => 10,
                    ]
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Distribution de réussite par niveau',
                    'font' => [
                        'size' => 18,
                        'weight' => '700',
                        'family' => 'Inter, system-ui, sans-serif'
                    ],
                    'padding' => 30
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(17, 24, 39, 0.9)',
                    'titleFont' => [
                        'size' => 14,
                        'weight' => '600'
                    ],
                    'bodyFont' => [
                        'size' => 13
                    ],
                    'padding' => 12,
                    'borderRadius' => 8,
                    'displayColors' => true,
                    'callbacks' => [
                        'label' => 'function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + \': \' + context.parsed + \' (\' + percentage + \'%)\';
                        }'
                    ]
                ]
            ],
            'layout' => [
                'padding' => [
                    'top' => 20,
                    'bottom' => 20
                ]
            ],
            'animation' => [
                'animateRotate' => true,
                'animateScale' => true,
                'duration' => 1200,
                'easing' => 'easeInOutQuart'
            ]
        ]);

        return $chart;
    }

    private function createDifficultQuestionsChart(?string $startDate, ?string $endDate, ?string $quizId): Chart
    {
        $chart = $this->chartBuilder->createChart('difficultQuestionsChart');

        // Analyser les questions les plus difficiles
        $queryBuilder = $this->statsRepository->createQueryBuilder('qs')
            ->select('qs.questionResults as questionResults');

        if ($startDate) {
            $queryBuilder->andWhere('qs.completedAt >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($startDate));
        }

        if ($endDate) {
            $queryBuilder->andWhere('qs.completedAt <= :endDate')
                ->setParameter('endDate', new \DateTimeImmutable($endDate . ' 23:59:59'));
        }

        if ($quizId) {
            $queryBuilder->andWhere('qs.quiz = :quizId')
                ->setParameter('quizId', $quizId);
        }

        $results = $queryBuilder->getQuery()->getResult();

        $questionStats = [];
        foreach ($results as $result) {
            $questionResults = $result['questionResults'];
            if (is_array($questionResults)) {
                foreach ($questionResults as $questionId => $isCorrect) {
                    if (!isset($questionStats[$questionId])) {
                        $questionStats[$questionId] = ['correct' => 0, 'total' => 0];
                    }
                    $questionStats[$questionId]['total']++;
                    if ($isCorrect) {
                        $questionStats[$questionId]['correct']++;
                    }
                }
            }
        }

        // Calculer les taux de réussite et trier par ordre croissant (plus difficile en premier)
        $questionRates = [];
        foreach ($questionStats as $questionId => $stats) {
            if ($stats['total'] > 0) {
                $rate = ($stats['correct'] / $stats['total']) * 100;
                $questionRates[$questionId] = $rate;
            }
        }

        asort($questionRates);
        $questionRates = array_slice($questionRates, 0, 10, true); // Top 10 des plus difficiles

        $labels = [];
        $data = [];
        $backgroundColors = [];
        $borderColors = [];
        
        foreach ($questionRates as $questionId => $rate) {
            $labels[] = 'Question ' . $questionId;
            $data[] = round($rate, 1);
            
            // Couleurs dynamiques basées sur la difficulté
            if ($rate < 40) {
                $backgroundColors[] = 'rgba(239, 68, 68, 0.85)';
                $borderColors[] = 'rgba(239, 68, 68, 1)';
            } elseif ($rate < 60) {
                $backgroundColors[] = 'rgba(251, 191, 36, 0.85)';
                $borderColors[] = 'rgba(251, 191, 36, 1)';
            } elseif ($rate < 80) {
                $backgroundColors[] = 'rgba(59, 130, 246, 0.85)';
                $borderColors[] = 'rgba(59, 130, 246, 1)';
            } else {
                $backgroundColors[] = 'rgba(34, 197, 94, 0.85)';
                $borderColors[] = 'rgba(34, 197, 94, 1)';
            }
        }

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Taux de réussite (%)',
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                    'data' => $data,
                    'borderSkipped' => false,
                    'maxBarThickness' => 60,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'indexAxis' => 'y', // Graphique horizontal pour meilleure lisibilité
            'plugins' => [
                'legend' => [
                    'display' => false
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Top 10 des questions les plus difficiles',
                    'font' => [
                        'size' => 18,
                        'weight' => '700',
                        'family' => 'Inter, system-ui, sans-serif'
                    ],
                    'padding' => 30
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(17, 24, 39, 0.9)',
                    'titleFont' => [
                        'size' => 14,
                        'weight' => '600'
                    ],
                    'bodyFont' => [
                        'size' => 13
                    ],
                    'padding' => 12,
                    'borderRadius' => 8,
                    'displayColors' => true,
                    'callbacks' => [
                        'label' => 'function(context) {
                            let difficulty = \'Très difficile\';
                            if (context.parsed.x >= 80) difficulty = \'Facile\';
                            else if (context.parsed.x >= 60) difficulty = \'Moyen\';
                            else if (context.parsed.x >= 40) difficulty = \'Difficile\';
                            
                            return \'Taux: \' + context.parsed.x + \'% (\' + difficulty + \')\';
                        }'
                    ]
                ]
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(156, 163, 175, 0.2)',
                        'drawBorder' => false
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 12,
                            'weight' => '500'
                        ],
                        'color' => '#6b7280',
                        'padding' => 10,
                        'callback' => 'function(value) {
                            return value + \'%\';
                        }'
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Taux de réussite (%)',
                        'font' => [
                            'size' => 14,
                            'weight' => '600'
                        ],
                        'color' => '#374151'
                    ]
                ],
                'y' => [
                    'grid' => [
                        'display' => false
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 12,
                            'weight' => '600'
                        ],
                        'color' => '#374151',
                        'padding' => 10
                    ]
                ]
            ],
            'animation' => [
                'duration' => 1400,
                'easing' => 'easeInOutQuart'
            ]
        ]);

        return $chart;
    }

    #[Route('/export/{format}', name: 'app_quiz_statistics_export')]
    public function export(Request $request, string $format): Response
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $quizId = $request->query->get('quiz_id');

        $queryBuilder = $this->statsRepository->createQueryBuilder('qs')
            ->leftJoin('qs.quiz', 'q')
            ->leftJoin('qs.student', 's')
            ->addSelect('q', 's')
            ->orderBy('qs.completedAt', 'DESC');

        if ($startDate) {
            $queryBuilder->andWhere('qs.completedAt >= :startDate')
                ->setParameter('startDate', new \DateTimeImmutable($startDate));
        }

        if ($endDate) {
            $queryBuilder->andWhere('qs.completedAt <= :endDate')
                ->setParameter('endDate', new \DateTimeImmutable($endDate . ' 23:59:59'));
        }

        if ($quizId) {
            $queryBuilder->andWhere('qs.quiz = :quizId')
                ->setParameter('quizId', $quizId);
        }

        $statistics = $queryBuilder->getQuery()->getResult();

        if ($format === 'csv') {
            return $this->exportToCsv($statistics);
        }

        throw $this->createNotFoundException('Format non supporté');
    }

    private function exportToCsv(array $statistics): Response
    {
        $csvContent = "ID Quiz,Titre Quiz,Étudiant,Score,Questions Totales,Réponses Correctes,Temps Moyen,Date Complétion\n";

        foreach ($statistics as $stat) {
            $csvContent .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s\n",
                $stat->getQuiz()->getId(),
                $stat->getQuiz()->getTitle(),
                $stat->getStudent()->getName() ?? $stat->getStudent()->getEmail(),
                $stat->getScore() . '%',
                $stat->getTotalQuestions(),
                $stat->getCorrectAnswers(),
                $stat->getAverageTimePerQuestion() . 's',
                $stat->getCompletedAt()->format('Y-m-d H:i:s')
            );
        }

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="quiz_statistics_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    #[Route('/api/refresh', name: 'app_quiz_statistics_api_refresh')]
    public function refreshData(Request $request): JsonResponse
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $quizId = $request->query->get('quiz_id');

        try {
            // Récupérer les statistiques mises à jour
            $queryBuilder = $this->statsRepository->createQueryBuilder('qs')
                ->leftJoin('qs.quiz', 'q')
                ->leftJoin('qs.student', 's')
                ->addSelect('q', 's')
                ->orderBy('qs.completedAt', 'DESC');

            if ($startDate) {
                $queryBuilder->andWhere('qs.completedAt >= :startDate')
                    ->setParameter('startDate', new \DateTimeImmutable($startDate));
            }

            if ($endDate) {
                $queryBuilder->andWhere('qs.completedAt <= :endDate')
                    ->setParameter('endDate', new \DateTimeImmutable($endDate . ' 23:59:59'));
            }

            if ($quizId) {
                $queryBuilder->andWhere('qs.quiz = :quizId')
                    ->setParameter('quizId', $quizId);
            }

            $statistics = $queryBuilder->getQuery()->getResult();

            // Calculer les statistiques sommaires
            $totalAttempts = count($statistics);
            $averageScore = 0;
            $averageTime = 0;
            $passRate = 0;

            if ($totalAttempts > 0) {
                $totalScore = 0;
                $totalTime = 0;
                $passCount = 0;

                foreach ($statistics as $stat) {
                    $totalScore += $stat->getScore();
                    $totalTime += $stat->getAverageTimePerQuestion();
                    if ($stat->getScore() >= 60) {
                        $passCount++;
                    }
                }

                $averageScore = round($totalScore / $totalAttempts, 1);
                $averageTime = round($totalTime / $totalAttempts, 1);
                $passRate = round(($passCount / $totalAttempts) * 100, 1);
            }

            // Créer les graphiques mis à jour
            $scoresChart = $this->createScoresChart($startDate, $endDate, $quizId);
            $successDistributionChart = $this->createSuccessDistributionChart($startDate, $endDate, $quizId);
            $difficultQuestionsChart = $this->createDifficultQuestionsChart($startDate, $endDate, $quizId);

            return new JsonResponse([
                'success' => true,
                'summary' => [
                    'totalAttempts' => $totalAttempts,
                    'averageScore' => $averageScore,
                    'averageTime' => $averageTime,
                    'passRate' => $passRate
                ],
                'charts' => [
                    'scoresChart' => $scoresChart->getData(),
                    'successDistributionChart' => $successDistributionChart->getData(),
                    'difficultQuestionsChart' => $difficultQuestionsChart->getData()
                ],
                'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors du rafraîchissement des données: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/summary', name: 'app_quiz_statistics_api_summary')]
    public function getSummaryStats(Request $request): JsonResponse
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $quizId = $request->query->get('quiz_id');

        try {
            $queryBuilder = $this->statsRepository->createQueryBuilder('qs')
                ->select('COUNT(qs.id) as totalAttempts, AVG(qs.score) as averageScore, AVG(qs.averageTimePerQuestion) as averageTime')
                ->leftJoin('qs.quiz', 'q');

            if ($startDate) {
                $queryBuilder->andWhere('qs.completedAt >= :startDate')
                    ->setParameter('startDate', new \DateTimeImmutable($startDate));
            }

            if ($endDate) {
                $queryBuilder->andWhere('qs.completedAt <= :endDate')
                    ->setParameter('endDate', new \DateTimeImmutable($endDate . ' 23:59:59'));
            }

            if ($quizId) {
                $queryBuilder->andWhere('qs.quiz = :quizId')
                    ->setParameter('quizId', $quizId);
            }

            $result = $queryBuilder->getQuery()->getSingleResult();

            // Calculer le taux de réussite
            $passRateQuery = $this->statsRepository->createQueryBuilder('qs')
                ->select('COUNT(qs.id) as passCount')
                ->where('qs.score >= 60');

            if ($startDate) {
                $passRateQuery->andWhere('qs.completedAt >= :startDate')
                    ->setParameter('startDate', new \DateTimeImmutable($startDate));
            }

            if ($endDate) {
                $passRateQuery->andWhere('qs.completedAt <= :endDate')
                    ->setParameter('endDate', new \DateTimeImmutable($endDate . ' 23:59:59'));
            }

            if ($quizId) {
                $passRateQuery->andWhere('qs.quiz = :quizId')
                    ->setParameter('quizId', $quizId);
            }

            $passResult = $passRateQuery->getQuery()->getSingleResult();
            $totalAttempts = (int)$result['totalAttempts'];
            $passRate = $totalAttempts > 0 ? round(($passResult['passCount'] / $totalAttempts) * 100, 1) : 0;

            return new JsonResponse([
                'success' => true,
                'summary' => [
                    'totalAttempts' => $totalAttempts,
                    'averageScore' => round($result['averageScore'], 1),
                    'averageTime' => round($result['averageTime'], 1),
                    'passRate' => $passRate
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la récupération du résumé: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/question-analysis', name: 'app_quiz_statistics_api_question_analysis')]
    public function getQuestionAnalysis(Request $request): JsonResponse
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $quizId = $request->query->get('quiz_id');

        try {
            // Récupérer les données des questions
            $queryBuilder = $this->statsRepository->createQueryBuilder('qs')
                ->select('qs.questionResults as questionResults');

            if ($startDate) {
                $queryBuilder->andWhere('qs.completedAt >= :startDate')
                    ->setParameter('startDate', new \DateTimeImmutable($startDate));
            }

            if ($endDate) {
                $queryBuilder->andWhere('qs.completedAt <= :endDate')
                    ->setParameter('endDate', new \DateTimeImmutable($endDate . ' 23:59:59'));
            }

            if ($quizId) {
                $queryBuilder->andWhere('qs.quiz = :quizId')
                    ->setParameter('quizId', $quizId);
            }

            $results = $queryBuilder->getQuery()->getResult();

            // Analyser les questions
            $questionStats = [];
            $totalQuestions = 0;
            $totalAttempts = count($results);

            foreach ($results as $result) {
                $questionResults = $result['questionResults'];
                if (is_array($questionResults)) {
                    foreach ($questionResults as $questionId => $isCorrect) {
                        if (!isset($questionStats[$questionId])) {
                            $questionStats[$questionId] = ['correct' => 0, 'total' => 0];
                        }
                        $questionStats[$questionId]['total']++;
                        if ($isCorrect) {
                            $questionStats[$questionId]['correct']++;
                        }
                        $totalQuestions = max($totalQuestions, $questionId);
                    }
                }
            }

            // Calculer les statistiques
            $successRates = [];
            foreach ($questionStats as $questionId => $stats) {
                if ($stats['total'] > 0) {
                    $successRates[$questionId] = ($stats['correct'] / $stats['total']) * 100;
                }
            }

            // Trier par taux de réussite
            asort($successRates);
            
            // Question la plus difficile et la plus facile
            $hardestQuestion = null;
            $easiestQuestion = null;
            $avgSuccessRate = 0;

            if (!empty($successRates)) {
                $hardestQuestionId = array_key_first($successRates);
                $easiestQuestionId = array_key_last($successRates);
                $hardestQuestion = "Question " . $hardestQuestionId . " (" . round($successRates[$hardestQuestionId], 1) . "%)";
                $easiestQuestion = "Question " . $easiestQuestionId . " (" . round($successRates[$easiestQuestionId], 1) . "%)";
                $avgSuccessRate = round(array_sum($successRates) / count($successRates), 1);
            }

            // Distribution par niveau de difficulté
            $difficultyDistribution = [0, 0, 0, 0, 0]; // Très Facile, Facile, Moyen, Difficile, Très Difficile
            foreach ($successRates as $rate) {
                if ($rate >= 90) {
                    $difficultyDistribution[0]++;
                } elseif ($rate >= 75) {
                    $difficultyDistribution[1]++;
                } elseif ($rate >= 60) {
                    $difficultyDistribution[2]++;
                } elseif ($rate >= 40) {
                    $difficultyDistribution[3]++;
                } else {
                    $difficultyDistribution[4]++;
                }
            }

            // Générer des recommandations
            $recommendations = [];
            if ($avgSuccessRate < 60) {
                $recommendations[] = "Le taux de réussite moyen est faible. Considérez revoir les questions difficiles.";
            }
            if ($difficultyDistribution[4] > count($successRates) * 0.3) {
                $recommendations[] = "Plus de 30% des questions sont très difficiles. Équilibrez mieux la difficulté.";
            }
            if ($difficultyDistribution[0] > count($successRates) * 0.5) {
                $recommendations[] = "Plus de 50% des questions sont très faciles. Augmentez le niveau de défi.";
            }

            return new JsonResponse([
                'success' => true,
                'totalQuestions' => $totalQuestions,
                'avgSuccessRate' => $avgSuccessRate,
                'hardestQuestion' => $hardestQuestion,
                'easiestQuestion' => $easiestQuestion,
                'difficultyDistribution' => $difficultyDistribution,
                'recommendations' => $recommendations
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de l\'analyse des questions: ' . $e->getMessage()
            ], 500);
        }
    }
}
