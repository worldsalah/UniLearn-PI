<?php

namespace App\Command;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-categories',
    description: 'Creates default categories for products'
)]
class CreateCategoriesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $categories = [
            'Web Development' => [
                'description' => 'Web development services including frontend, backend, and full-stack development',
                'icon' => 'code',
                'color' => '#007bff',
            ],
            'Design' => [
                'description' => 'Design services including UI/UX, graphic design, and branding',
                'icon' => 'palette',
                'color' => '#ff6b6b',
            ],
            'Marketing' => [
                'description' => 'Marketing services including digital marketing, SEO, and social media',
                'icon' => 'megaphone',
                'color' => '#28a745',
            ],
            'Writing' => [
                'description' => 'Writing services including content writing, copywriting, and editing',
                'icon' => 'pen',
                'color' => '#ffc107',
            ],
            'Other' => [
                'description' => 'Other professional services',
                'icon' => 'briefcase',
                'color' => '#6c757d',
            ],
        ];

        foreach ($categories as $name => $data) {
            // Check if category already exists
            $existingCategory = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => $name]);

            if (!$existingCategory) {
                $category = new Category();
                $category->setName($name);
                $category->setDescription($data['description']);
                $category->setIcon($data['icon']);
                $category->setColor($data['color']);

                $this->entityManager->persist($category);
                $output->writeln('Created category: '.$name);
            } else {
                $output->writeln('Category already exists: '.$name);
            }
        }

        $this->entityManager->flush();
        $output->writeln('Categories created successfully!');

        return Command::SUCCESS;
    }
}
