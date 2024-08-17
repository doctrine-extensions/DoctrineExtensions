<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Command\PrintCategoryTranslationTreeCommand;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Gedmo\DoctrineExtensions;
use Symfony\Component\Console\Application;

/** @var EntityManager $em */
$em = include __DIR__.'/../em.php';

$entityManagerProvider = new SingleManagerProvider($em);

$cli = new Application('Doctrine Extensions Example Application', DoctrineExtensions::VERSION);
$cli->setCatchExceptions(true);
$cli->setHelperSet(ConsoleRunner::createHelperSet($em));

// Use the ORM's console runner to register the default commands available from the DBAL and ORM for the environment
ConsoleRunner::addCommands($cli, $entityManagerProvider);

// Register our example app commands
$cli->addCommands([
    new PrintCategoryTranslationTreeCommand(),
]);

return $cli;
