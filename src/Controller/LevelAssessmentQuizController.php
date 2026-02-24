<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LevelAssessmentQuizController extends AbstractController
{
    #[Route('/level-assessment/quiz/{categoryId}', name: 'app_level_assessment_quiz')]
    public function quiz(CategoryRepository $categoryRepository, int $categoryId): Response
    {
        // Get the category
        $category = $categoryRepository->find($categoryId);
        
        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        // Define quiz questions based on category
        $quizQuestions = $this->getQuizQuestions($category);

        return $this->render('level_assessment/quiz.html.twig', [
            'category' => $category,
            'questions' => $quizQuestions,
        ]);
    }

    private function getQuizQuestions(Category $category): array
    {
        $categoryName = strtolower($category->getName());
        
        // Define questions for different categories
        $questionBank = [
            'programming' => [
                [
                    'question' => 'What is your experience with programming?',
                    'options' => [
                        'A' => 'I have never programmed before',
                        'B' => 'I have done some basic tutorials',
                        'C' => 'I can build simple applications',
                        'D' => 'I am an experienced developer'
                    ],
                    'correct' => null // No correct answer for assessment
                ],
                [
                    'question' => 'Which programming concepts are you familiar with?',
                    'options' => [
                        'A' => 'Variables and basic syntax',
                        'B' => 'Functions and loops',
                        'C' => 'Object-oriented programming',
                        'D' => 'Advanced algorithms and data structures'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'How comfortable are you with debugging code?',
                    'options' => [
                        'A' => 'I find debugging very difficult',
                        'B' => 'I can fix simple bugs with help',
                        'C' => 'I can debug most issues independently',
                        'D' => 'I enjoy complex debugging challenges'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'What type of projects interest you most?',
                    'options' => [
                        'A' => 'Simple websites and basic apps',
                        'B' => 'Web applications with databases',
                        'C' => 'Mobile applications',
                        'D' => 'Complex systems or enterprise software'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'How do you prefer to learn programming?',
                    'options' => [
                        'A' => 'Step-by-step tutorials',
                        'B' => 'Video courses and demonstrations',
                        'C' => 'Hands-on projects',
                        'D' => 'Documentation and technical articles'
                    ],
                    'correct' => null
                ]
            ],
            'design' => [
                [
                    'question' => 'What is your experience with design software?',
                    'options' => [
                        'A' => 'I have never used design software',
                        'B' => 'I have tried basic tools like Canva',
                        'C' => 'I am comfortable with professional tools',
                        'D' => 'I am an expert in multiple design programs'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'Which design areas interest you most?',
                    'options' => [
                        'A' => 'Basic graphic design and social media',
                        'B' => 'Web design and user interfaces',
                        'C' => 'User experience and research',
                        'D' => 'Brand identity and advanced design systems'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'How would you describe your design skills?',
                    'options' => [
                        'A' => 'I am just starting out',
                        'B' => 'I understand basic design principles',
                        'C' => 'I can create professional designs',
                        'D' => 'I have advanced design and conceptual skills'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'What design tools have you used?',
                    'options' => [
                        'A' => 'No professional design tools',
                        'B' => 'Basic tools like Canva or Paint',
                        'C' => 'Adobe Creative Suite (Photoshop, Illustrator)',
                        'D' => 'Multiple professional tools including Figma, Sketch, etc.'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'What type of design projects do you want to create?',
                    'options' => [
                        'A' => 'Simple graphics and social media posts',
                        'B' => 'Website designs and mockups',
                        'C' => 'Complete brand identities',
                        'D' => 'Complex design systems and user experiences'
                    ],
                    'correct' => null
                ]
            ],
            'business' => [
                [
                    'question' => 'What is your business experience?',
                    'options' => [
                        'A' => 'I have no business experience',
                        'B' => 'I have basic business knowledge',
                        'C' => 'I have worked in business roles',
                        'D' => 'I have management or entrepreneurial experience'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'Which business topics interest you most?',
                    'options' => [
                        'A' => 'Basic business concepts and terminology',
                        'B' => 'Marketing and sales fundamentals',
                        'C' => 'Business strategy and management',
                        'D' => 'Advanced business analytics and operations'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'What are your business learning goals?',
                    'options' => [
                        'A' => 'Understanding basic business principles',
                        'B' => 'Learning marketing and sales techniques',
                        'C' => 'Developing management and leadership skills',
                        'D' => 'Mastering strategic business planning'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'How do you prefer to learn business concepts?',
                    'options' => [
                        'A' => 'Simple explanations and examples',
                        'B' => 'Case studies and real-world examples',
                        'C' => 'Interactive projects and simulations',
                        'D' => 'Advanced theory and strategic analysis'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'What business skills do you want to develop?',
                    'options' => [
                        'A' => 'Basic business communication',
                        'B' => 'Marketing and customer service',
                        'C' => 'Financial planning and analysis',
                        'D' => 'Strategic planning and leadership'
                    ],
                    'correct' => null
                ]
            ],
            'languages' => [
                [
                    'question' => 'What is your current language level?',
                    'options' => [
                        'A' => 'Complete beginner',
                        'B' => 'I know some basic phrases',
                        'C' => 'I can have simple conversations',
                        'D' => 'I am fluent or advanced'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'How do you prefer to learn languages?',
                    'options' => [
                        'A' => 'Through basic vocabulary and phrases',
                        'B' => 'With structured grammar lessons',
                        'C' => 'Through conversation and practice',
                        'D' => 'Immersion and advanced content'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'What is your main language learning goal?',
                    'options' => [
                        'A' => 'Basic travel and conversation',
                        'B' => 'Professional communication',
                        'C' => 'Academic or technical language',
                        'D' => 'Native-level fluency'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'Which language aspects are most important to you?',
                    'options' => [
                        'A' => 'Speaking and pronunciation',
                        'B' => 'Grammar and writing',
                        'C' => 'Conversation and comprehension',
                        'D' => 'Cultural understanding and nuance'
                    ],
                    'correct' => null
                ],
                [
                    'question' => 'How much time can you dedicate to language learning?',
                    'options' => [
                        'A' => 'A few minutes per day',
                        'B' => '30 minutes to 1 hour daily',
                        'C' => '1-2 hours per day',
                        'D' => 'Several hours per day for intensive learning'
                    ],
                    'correct' => null
                ]
            ]
        ];

        // Return questions for the category, or default questions if category not found
        foreach ($questionBank as $key => $questions) {
            if (strpos($categoryName, $key) !== false) {
                return $questions;
            }
        }

        // Default questions if no specific category matches
        return [
            [
                'question' => 'What is your experience level in this subject?',
                'options' => [
                    'A' => 'Complete beginner',
                    'B' => 'Some basic knowledge',
                    'C' => 'Intermediate experience',
                    'D' => 'Advanced level'
                ],
                'correct' => null
            ],
            [
                'question' => 'How do you prefer to learn?',
                'options' => [
                    'A' => 'Step-by-step guidance',
                    'B' => 'Visual demonstrations',
                    'C' => 'Hands-on practice',
                    'D' => 'Self-directed exploration'
                ],
                'correct' => null
            ],
            [
                'question' => 'What are your learning goals?',
                'options' => [
                    'A' => 'Basic understanding',
                    'B' => 'Practical skills for work',
                    'C' => 'Deep expertise',
                    'D' => 'Professional mastery'
                ],
                'correct' => null
            ]
        ];
    }
}
