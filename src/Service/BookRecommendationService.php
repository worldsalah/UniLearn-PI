<?php

namespace App\Service;

use App\Entity\Course;

class BookRecommendationService
{
    private array $bookDatabase;

    public function __construct()
    {
        $this->bookDatabase = $this->initializeBookDatabase();
    }

    public function getRecommendationsForCourse(Course $course): array
    {
        $courseTitle = strtolower($course->getTitle());
        $courseDescription = strtolower($course->getShortDescription() ?? '');

        // Find relevant books based on course content
        $recommendations = [];

        foreach ($this->bookDatabase as $category => $books) {
            if ($this->isCourseRelevantToCategory($courseTitle, $courseDescription, $category)) {
                $recommendations = array_merge($recommendations, array_slice($books, 0, 3));
                break;
            }
        }

        // If no specific category matches, provide general recommendations
        if (empty($recommendations)) {
            $recommendations = $this->getGeneralRecommendations($courseTitle, $courseDescription);
        }

        return array_slice($recommendations, 0, 3);
    }

    private function isCourseRelevantToCategory(string $courseTitle, string $courseDescription, string $category): bool
    {
        $keywords = array_merge(
            explode(' ', $courseTitle),
            explode(' ', $courseDescription)
        );

        $categoryKeywords = [
            'programming' => ['programming', 'code', 'development', 'software', 'web', 'app', 'javascript', 'python', 'java', 'php', 'html', 'css', 'react', 'angular', 'vue', 'node', 'database', 'api', 'framework'],
            'design' => ['design', 'ui', 'ux', 'user', 'interface', 'graphic', 'color', 'typography', 'layout', 'visual', 'creative', 'art', 'style', 'css', 'sass', 'bootstrap', 'tailwind'],
            'business' => ['business', 'management', 'marketing', 'sales', 'strategy', 'finance', 'accounting', 'entrepreneur', 'startup', 'leadership', 'project'],
            'data_science' => ['data', 'science', 'analytics', 'statistics', 'machine', 'learning', 'ai', 'python', 'r', 'sql', 'excel', 'tableau', 'power bi'],
            'web_development' => ['html', 'css', 'javascript', 'web', 'frontend', 'backend', 'fullstack', 'react', 'vue', 'angular', 'node', 'php', 'laravel', 'symfony'],
            'mobile_development' => ['mobile', 'ios', 'android', 'swift', 'kotlin', 'react native', 'flutter', 'app', 'phone'],
            'security' => ['security', 'cybersecurity', 'hacking', 'network', 'penetration', 'encryption', 'authentication', 'firewall', 'vpn'],
            'database' => ['database', 'sql', 'mysql', 'postgresql', 'oracle', 'nosql', 'mongodb', 'redis'],
            'devops' => ['devops', 'docker', 'kubernetes', 'aws', 'azure', 'cloud', 'deployment', 'ci', 'cd', 'infrastructure'],
        ];

        foreach ($keywords as $keyword) {
            if (isset($categoryKeywords[$category]) && in_array($keyword, $categoryKeywords[$category])) {
                return true;
            }
        }

        return false;
    }

    private function getGeneralRecommendations(string $courseTitle, string $courseDescription): array
    {
        // General recommendations based on common programming/tech topics
        $generalBooks = [
            [
                'title' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                'author' => 'Robert C. Martin',
                'reason' => 'Essential reading for writing clean, maintainable code.'
            ],
            [
                'title' => 'The Pragmatic Programmer: Your Journey to Mastery',
                'author' => 'David Thomas',
                'reason' => 'Practical advice for becoming a better programmer.'
            ],
            [
                'title' => 'Design Patterns: Elements of Reusable Object-Oriented Software',
                'author' => 'Erich Gamma',
                'reason' => 'Fundamental concepts for software design and architecture.'
            ],
            [
                'title' => 'Refactoring: Improving the Design of Existing Code',
                'author' => 'Martin Fowler',
                'reason' => 'Essential techniques for code improvement and maintenance.'
            ]
        ];

        return $generalBooks;
    }

    private function initializeBookDatabase(): array
    {
        return [
            'programming' => [
                [
                    'title' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                    'author' => 'Robert C. Martin',
                    'reason' => 'Essential reading for writing clean, maintainable code.'
                ],
                [
                    'title' => 'The Pragmatic Programmer: Your Journey to Mastery',
                    'author' => 'David Thomas',
                    'reason' => 'Practical advice for becoming a better programmer.'
                ],
                [
                    'title' => 'Design Patterns: Elements of Reusable Object-Oriented Software',
                    'author' => 'Erich Gamma',
                    'reason' => 'Fundamental concepts for software design and architecture.'
                ],
                [
                    'title' => 'Refactoring: Improving the Design of Existing Code',
                    'author' => 'Martin Fowler',
                    'reason' => 'Essential techniques for code improvement and maintenance.'
                ],
                [
                    'title' => 'Code Complete: A Practical Handbook of Software Construction',
                    'author' => 'Steve McConnell',
                    'reason' => 'Comprehensive guide to software development best practices.'
                ],
                [
                    'title' => 'The Mythical Man-Month',
                    'author' => 'Frederick Brooks',
                    'reason' => 'Classic essays on software engineering and project management.'
                ]
            ],
            'web_development' => [
                [
                    'title' => 'JavaScript: The Good Parts',
                    'author' => 'Douglas Crockford',
                    'reason' => 'Deep dive into JavaScript concepts and best practices.'
                ],
                [
                    'title' => 'Eloquent JavaScript',
                    'author' => 'Marijn Haverbeke',
                    'reason' => 'Modern JavaScript development patterns and techniques.'
                ],
                [
                    'title' => 'CSS: The Definitive Guide',
                    'author' => 'Eric Meyer',
                    'reason' => 'Comprehensive reference for CSS styling and layout.'
                ],
                [
                    'title' => 'Learning Web Design',
                    'author' => 'Jennifer Robbins',
                    'reason' => 'User experience and interface design fundamentals.'
                ],
                [
                    'title' => 'Don\'t Make Me Think',
                    'author' => 'Steve Krug',
                    'reason' => 'Essential usability and web design principles.'
                ]
            ],
            'design' => [
                [
                    'title' => 'The Design of Everyday Things',
                    'author' => 'Don Norman',
                    'reason' => 'Fundamental principles of user-centered design.'
                ],
                [
                    'title' => 'Universal Principles of Design',
                    'author' => 'William Lidwell',
                    'reason' => 'Timeless design principles and patterns.'
                ],
                [
                    'title' => 'Thinking with Type',
                    'author' => 'Ellen Lupton',
                    'reason' => 'Typography fundamentals for designers.'
                ],
                [
                    'title' => 'Grid Systems in Graphic Design',
                    'author' => 'Josef Müller-Brockmann',
                    'reason' => 'Essential layout and composition principles.'
                ]
            ],
            'business' => [
                [
                    'title' => 'The Lean Startup',
                    'author' => 'Eric Ries',
                    'reason' => 'How to build successful businesses with minimal resources.'
                ],
                [
                    'title' => 'Zero to One',
                    'author' => 'Peter Thiel',
                    'reason' => 'Building the future through technology and innovation.'
                ],
                [
                    'title' => 'The 7 Habits of Highly Effective People',
                    'author' => 'Stephen Covey',
                    'reason' => 'Personal and professional effectiveness principles.'
                ],
                [
                    'title' => 'Good to Great',
                    'author' => 'Jim Collins',
                    'reason' => 'How companies transition from good to great.'
                ]
            ],
            'data_science' => [
                [
                    'title' => 'Python for Data Analysis',
                    'author' => 'Wes McKinney',
                    'reason' => 'Practical data science with Python programming.'
                ],
                [
                    'title' => 'Hands-On Machine Learning',
                    'author' => 'Aurélien Géron',
                    'reason' => 'Introduction to ML concepts with practical examples.'
                ],
                [
                    'title' => 'Data Science for Business',
                    'author' => 'Foster Provost',
                    'reason' => 'Applying data science to solve business problems.'
                ],
                [
                    'title' => 'Storytelling with Data',
                    'author' => 'Cole Nussbaumer Knaflic',
                    'reason' => 'Communicating data insights effectively.'
                ]
            ]
        ];
    }
}
