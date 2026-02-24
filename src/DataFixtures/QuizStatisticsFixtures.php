<?php

namespace App\DataFixtures;

use App\Entity\QuizStatistics;
use App\Entity\Quiz;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class QuizStatisticsFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer les quizzes et utilisateurs existants
        $quizzes = $manager->getRepository(Quiz::class)->findAll();
        $users = $manager->getRepository(User::class)->findAll();

        if (empty($quizzes) || empty($users)) {
            return;
        }

        // Filtrer les utilisateurs qui peuvent être des étudiants
        $students = array_filter($users, function($user) {
            return in_array('ROLE_STUDENT', $user->getRoles()) || 
                   (!in_array('ROLE_ADMIN', $user->getRoles()) && !in_array('ROLE_INSTRUCTOR', $user->getRoles()));
        });

        if (empty($students)) {
            return;
        }

        // Créer des données statistiques pour les 30 derniers jours
        $startDate = new \DateTimeImmutable('-30 days');
        $endDate = new \DateTimeImmutable();

        foreach ($quizzes as $quiz) {
            // Générer entre 3 et 8 statistiques par quiz
            $statsCount = rand(3, 8);
            
            for ($i = 0; $i < $statsCount; $i++) {
                $statistic = new QuizStatistics();
                
                // Assigner un étudiant aléatoire
                $student = $students[array_rand($students)];
                $statistic->setStudent($student);
                $statistic->setQuiz($quiz);

                // Générer un score réaliste
                $score = rand(45, 100);
                $statistic->setScore($score);

                // Générer le nombre de questions et réponses correctes
                $totalQuestions = rand(10, 25);
                $correctAnswers = intval(($score / 100) * $totalQuestions);
                $statistic->setTotalQuestions($totalQuestions);
                $statistic->setCorrectAnswers($correctAnswers);

                // Générer une date de complétion aléatoire dans les 30 derniers jours
                $randomTimestamp = rand($startDate->getTimestamp(), $endDate->getTimestamp());
                $completionDate = \DateTimeImmutable::createFromMutable((new \DateTime())->setTimestamp($randomTimestamp));
                $statistic->setCompletedAt($completionDate);

                // Générer un temps moyen par question (entre 15 et 120 secondes)
                $averageTime = rand(15, 120) + (rand(0, 99) / 100);
                $statistic->setAverageTimePerQuestion($averageTime);

                // Générer des résultats par question
                $questionResults = [];
                for ($q = 1; $q <= $totalQuestions; $q++) {
                    // Rendre certaines questions plus difficiles que d'autres
                    $difficulty = rand(0, 100);
                    $isCorrect = $difficulty < $score;
                    $questionResults[$q] = $isCorrect;
                }
                $statistic->setQuestionResults($questionResults);

                // Assigner un niveau de difficulté basé sur le score
                if ($score >= 90) {
                    $difficultyLevel = 1; // Facile
                } elseif ($score >= 75) {
                    $difficultyLevel = 2; // Moyen
                } elseif ($score >= 60) {
                    $difficultyLevel = 3; // Difficile
                } else {
                    $difficultyLevel = 4; // Très difficile
                }
                $statistic->setDifficultyLevel($difficultyLevel);

                $manager->persist($statistic);
            }
        }

        $manager->flush();
    }
}
