<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$em = include __DIR__.'/../em.php';

$cli = new Symfony\Component\Console\Application('My CLI interface', '1.0.0');
$cli->setCatchExceptions(true);
// commands
$cli->addCommands([
    // DBAL Commands
    new Doctrine\DBAL\Tools\Console\Command\RunSqlCommand(),
    new Doctrine\DBAL\Tools\Console\Command\ImportCommand(),

    // ORM Commands
    new Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand(),
    new Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand(),
    new Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand(),
    new Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand(),
    new Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand(),
    new Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand(),
    new Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand(),
    new Doctrine\ORM\Tools\Console\Command\ConvertDoctrine1SchemaCommand(),
    new Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand(),
    new Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand(),
    new Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand(),
    new Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand(),
    new Doctrine\ORM\Tools\Console\Command\RunDqlCommand(),
    new Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand(),
]);
// helpers
$helpers = [
    'db' => new Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
];
foreach ($helpers as $name => $helper) {
    $cli->getHelperSet()->set($helper, $name);
}

return $cli;
