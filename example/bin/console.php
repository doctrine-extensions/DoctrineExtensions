<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/** @var \Doctrine\ORM\EntityManager $em */
$em = include __DIR__.'/../em.php';

$entityManagerProvider = new \Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider($em);

$cli = new \Symfony\Component\Console\Application('Doctrine Extensions Example Application', \Gedmo\DoctrineExtensions::VERSION);
$cli->setCatchExceptions(true);
$cli->setHelperSet(\Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($em));

// Use the ORM's console runner to register the default commands available from the DBAL and ORM for the environment
\Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($cli, $entityManagerProvider);

// Register our example app commands
$cli->addCommands([
    new \App\Command\PrintCategoryTranslationTreeCommand(),
]);

return $cli;
