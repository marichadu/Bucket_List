<?php
namespace App\Command;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed-categories')]
class SeedCategoriesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $categories = ['Travel & Adventure', 'Sport', 'Entertainment', 'Human Relations', 'Others'];

        foreach ($categories as $categoryName) {
            // Check if the category already exists
            $existingCategory = $this->entityManager
                ->getRepository(Category::class)
                ->findOneBy(['name' => $categoryName]);

            if (!$existingCategory) {
                $category = new Category();
                $category->setName($categoryName);
                $this->entityManager->persist($category);
                $output->writeln("Category '$categoryName' added.");
            } else {
                $output->writeln("Category '$categoryName' already exists.");
            }
        }

        $this->entityManager->flush();

        $output->writeln('Categories seeded successfully.');

        return Command::SUCCESS;
    }
}