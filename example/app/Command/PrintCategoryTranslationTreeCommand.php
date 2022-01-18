<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Category;
use App\Entity\CategoryTranslation;
use App\Entity\Repository\CategoryRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PrintCategoryTranslationTreeCommand extends Command
{
    protected static $defaultName = 'app:print-category-translation-tree';
    protected static $defaultDescription = 'Seeds an example category tree with translations and prints the tree.';

    protected function configure(): void
    {
        // Kept for compatibility with Symfony 5.2 and older, which do not support lazy descriptions
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var EntityManagerHelper $helper */
        $helper = $this->getHelper('em');

        $em = $helper->getEntityManager();

        /** @var CategoryRepository $repository */
        $repository = $em->getRepository(Category::class);

        /** @var Category|null $food */
        $food = $repository->findOneByTitle('Food');

        // If we don't have our examples in the database already, seed them
        if (null === $food) {
            $food = new Category();
            $food->setTitle('Food');
            $food->addTranslation(new CategoryTranslation('lt', 'title', 'Maistas'));

            $fruits = new Category();
            $fruits->setParent($food);
            $fruits->setTitle('Fruits');
            $fruits->addTranslation(new CategoryTranslation('lt', 'title', 'Vaisiai'));

            $apple = new Category();
            $apple->setParent($fruits);
            $apple->setTitle('Apple');
            $apple->addTranslation(new CategoryTranslation('lt', 'title', 'Obuolys'));

            $milk = new Category();
            $milk->setParent($food);
            $milk->setTitle('Milk');
            $milk->addTranslation(new CategoryTranslation('lt', 'title', 'Pienas'));

            $em->persist($food);
            $em->persist($milk);
            $em->persist($fruits);
            $em->persist($apple);
            $em->flush();
        }

        // Create a query to fetch the tree nodes
        $query = $em->createQueryBuilder()
            ->select('node')
            ->from(Category::class, 'node')
            ->orderBy('node.root')
            ->addOrderBy('node.lft')
            ->getQuery()
        ;

        // Set the hint to translate nodes
        $query->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            TranslationWalker::class
        );

        $treeDecorationOptions = [
            'decorate' => true,
            'rootOpen' => '',
            'rootClose' => '',
            'childOpen' => '',
            'childClose' => '',
            'nodeDecorator' => static function ($node): string {
                return str_repeat('-', $node['level']).$node['title'].PHP_EOL;
            },
        ];

        // Build the tree in English
        $output->writeln('English:');
        $output->writeln($repository->buildTree($query->getArrayResult(), $treeDecorationOptions));

        // Change the locale and build the tree in Lithuanian
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'lt');
        $output->writeln('Lithuanian:');
        $output->writeln($repository->buildTree($query->getArrayResult(), $treeDecorationOptions));

        return 0;
    }
}
