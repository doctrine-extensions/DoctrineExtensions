<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Loggable\LogEntryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'gedmo:loggable:migrate-data-to-json',
    description: 'Migrate Loggable data column from PHP serialized values to JSON.',
)]
final class MigrateDataToJsonCommand extends Command
{
    /**
     * Matches the CREATE TABLE header and captures the table identifier as group 2.
     *
     * Supported identifier formats:
     * - "quoted"
     * - `backticked`
     * - [bracketed]
     * - unquoted_identifier
     */
    private const SQLITE_CREATE_TABLE_HEADER_PATTERN = '/^(CREATE\\s+TABLE\\s+)((?:"[^"]*"|`[^`]*`|\\[[^\\]]*\\]|[a-zA-Z_][a-zA-Z0-9_]*))(\\s*\\()/i';

    /**
     * Matches the `data` column identifier at a column-definition boundary.
     * Prevents matching identifiers like `data_type`.
     */
    private const SQLITE_DATA_COLUMN_PATTERN = '/(?<=\\(|,)\\s*("data"|`data`|\\[data\\]|(?<![a-zA-Z0-9_])data(?![a-zA-Z0-9_]))(?=\\s)/i';
    private const CONNECTION_IDENTITY_KEYS = ['url', 'driver', 'host', 'port', 'dbname', 'path', 'memory', 'unix_socket'];

    private Connection $connection;
    private ?ManagerRegistry $managerRegistry;

    public function __construct(Connection $connection, ?ManagerRegistry $managerRegistry = null)
    {
        parent::__construct();

        $this->connection = $connection;
        $this->managerRegistry = $managerRegistry;
    }

    protected function configure(): void
    {
        $this
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Rows per round-trip.', '500')
            ->addOption('drop-legacy', null, InputOption::VALUE_NONE, 'Drop the data_serialized column after successful migration.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = (int) $input->getOption('batch-size');
        $dropLegacy = (bool) $input->getOption('drop-legacy');

        if ($batchSize < 1) {
            $output->writeln('<error>--batch-size must be greater than 0.</error>');

            return self::FAILURE;
        }

        $tables = $this->resolveLogEntryTables($this->connection);

        if ([] === $tables) {
            $output->writeln('<error>Could not resolve any ORM log entry table from Doctrine metadata for the injected connection.</error>');

            return self::FAILURE;
        }

        $output->writeln(sprintf(
            'Resolved %d log entry table(s): %s',
            count($tables),
            implode(', ', array_map(static fn (string $table): string => sprintf("'%s'", $table), $tables))
        ));

        try {
            foreach ($tables as $table) {
                $this->migrateTable($this->connection, $table, $batchSize, $dropLegacy, $output);
            }
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function migrateTable(
        Connection $connection,
        string $table,
        int $batchSize,
        bool $dropLegacy,
        OutputInterface $output
    ): void {
        $output->writeln('');
        $output->writeln(sprintf("Migrating table '%s'...", $table));

        $platform = $connection->getDatabasePlatform();
        $schemaManager = method_exists($connection, 'createSchemaManager')
            ? $connection->createSchemaManager()
            : $connection->getSchemaManager(); // DBAL 3 fallback

        $quotedTable = $platform->quoteSingleIdentifier($table);
        $columns = $schemaManager->listTableColumns($table);
        $columnNames = array_keys($columns);

        $hasData = in_array('data', $columnNames, true);
        $hasDataSerialized = in_array('data_serialized', $columnNames, true);

        if (!$hasData && !$hasDataSerialized) {
            throw new \RuntimeException(sprintf("Neither 'data' nor 'data_serialized' column found in table '%s'. Nothing to migrate.", $table));
        }

        $platformName = strtolower(get_class($platform));
        $isSqlite = str_contains($platformName, 'sqlite');

        if ($hasData && !$hasDataSerialized) {
            $output->writeln(sprintf("Step 1: Renaming column 'data' to 'data_serialized' in table '%s'...", $table));

            if ($isSqlite) {
                $this->migrateViaSqliteRecreate($connection, $platform, $table, $quotedTable, $output);
                $this->finalize($connection, $platform, $table, $quotedTable, $dropLegacy, $output);

                return;
            }

            $connection->executeStatement(sprintf(
                'ALTER TABLE %s RENAME COLUMN %s TO %s',
                $quotedTable,
                $platform->quoteSingleIdentifier('data'),
                $platform->quoteSingleIdentifier('data_serialized')
            ));
            $output->writeln('  Done.');
        } elseif ($hasDataSerialized && !$hasData) {
            $output->writeln(sprintf("Step 1: Column 'data_serialized' already exists in table '%s'; skipping rename.", $table));
        } else {
            $output->writeln(sprintf("Step 1: Both 'data' and 'data_serialized' columns exist in table '%s'; assuming rename was already performed.", $table));
        }

        $columnsAfterRename = array_keys($schemaManager->listTableColumns($table));
        if (!in_array('data', $columnsAfterRename, true)) {
            $output->writeln(sprintf("Step 2: Adding new 'data' column to table '%s'...", $table));
            $clobType = $platform->getClobTypeDeclarationSQL([]);
            $connection->executeStatement(sprintf(
                'ALTER TABLE %s ADD COLUMN %s %s DEFAULT NULL',
                $quotedTable,
                $platform->quoteSingleIdentifier('data'),
                $clobType
            ));
            $output->writeln('  Done.');
        } else {
            $output->writeln(sprintf("Step 2: Column 'data' already exists in table '%s'; skipping ADD COLUMN.", $table));
        }

        $output->writeln(sprintf("Step 3: Converting serialized data to JSON in table '%s'...", $table));
        $converted = $this->convertRows($connection, $platform, $quotedTable, $batchSize, $output);
        $output->writeln(sprintf('  Converted %d row(s).', $converted));

        $this->finalize($connection, $platform, $table, $quotedTable, $dropLegacy, $output);
    }

    /**
     * @return list<string>
     */
    private function resolveLogEntryTables(Connection $connection): array
    {
        if (null === $this->managerRegistry) {
            return [];
        }

        $connectionIdentity = $this->getConnectionIdentity($connection);
        $tablesByName = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            if (!method_exists($manager, 'getConnection')) {
                continue;
            }

            $managerConnection = $manager->getConnection();
            if (!$managerConnection instanceof Connection) {
                continue;
            }

            if (!$this->isSameConnection($managerConnection, $connection, $connectionIdentity)) {
                continue;
            }

            $allMetadata = $manager->getMetadataFactory()->getAllMetadata();

            foreach ($allMetadata as $metadata) {
                if (!method_exists($metadata, 'getTableName')) {
                    continue;
                }

                $className = $metadata->getName();
                if (!is_a($className, LogEntryInterface::class, true)) {
                    continue;
                }

                if (property_exists($metadata, 'isMappedSuperclass') && $metadata->isMappedSuperclass) {
                    continue;
                }

                $tablesByName[$metadata->getTableName()] = true;
            }
        }

        return array_keys($tablesByName);
    }

    /**
     * @param array<string, mixed> $connectionIdentity
     */
    private function isSameConnection(Connection $first, Connection $second, array $connectionIdentity): bool
    {
        return $first === $second || $this->getConnectionIdentity($first) === $connectionIdentity;
    }

    /**
     * @return array<string, mixed>
     */
    private function getConnectionIdentity(Connection $connection): array
    {
        $params = $connection->getParams();
        $identity = [];

        foreach (self::CONNECTION_IDENTITY_KEYS as $key) {
            if (array_key_exists($key, $params)) {
                $identity[$key] = $params[$key];
            }
        }

        ksort($identity);

        return $identity;
    }

    private function convertRows(
        Connection $connection,
        AbstractPlatform $platform,
        string $quotedTable,
        int $batchSize,
        OutputInterface $output
    ): int {
        $converted = 0;
        $offset = 0;

        $qData = $platform->quoteSingleIdentifier('data');
        $qDataSerialized = $platform->quoteSingleIdentifier('data_serialized');
        $qId = $platform->quoteSingleIdentifier('id');

        while (true) {
            $rows = $connection->fetchAllAssociative(
                sprintf(
                    'SELECT %s, %s FROM %s WHERE %s IS NOT NULL AND %s IS NULL LIMIT %d OFFSET %d',
                    $qId,
                    $qDataSerialized,
                    $quotedTable,
                    $qDataSerialized,
                    $qData,
                    $batchSize,
                    $offset
                )
            );

            if ([] === $rows) {
                break;
            }

            foreach ($rows as $row) {
                $serialized = $row['data_serialized'];
                if (null === $serialized) {
                    continue;
                }

                [$ok, $deserialized] = $this->safeUnserialize($serialized);
                if (!$ok) {
                    $output->writeln(sprintf('  <comment>Warning: could not unserialize row id=%s – skipping.</comment>', $row['id']));
                    continue;
                }

                try {
                    $json = json_encode($deserialized, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                } catch (\JsonException $e) {
                    $output->writeln(sprintf('  <comment>Warning: could not JSON-encode row id=%s (%s) – skipping.</comment>', $row['id'], $e->getMessage()));
                    continue;
                }

                $connection->executeStatement(
                    sprintf('UPDATE %s SET %s = ? WHERE %s = ?', $quotedTable, $qData, $qId),
                    [$json, $row['id']]
                );
                ++$converted;
            }

            $offset += $batchSize;
        }

        return $converted;
    }

    private function migrateViaSqliteRecreate(
        Connection $connection,
        AbstractPlatform $platform,
        string $table,
        string $quotedTable,
        OutputInterface $output
    ): void {
        $tmpTable = $table.'_migration_tmp';
        $quotedTmp = $platform->quoteSingleIdentifier($tmpTable);

        $createSql = $connection->fetchOne(
            "SELECT sql FROM sqlite_master WHERE type='table' AND name=?",
            [$table]
        );

        if (false === $createSql || null === $createSql) {
            throw new \RuntimeException(sprintf("Could not read schema for table '%s'.", $table));
        }

        $tmpCreate = preg_replace(
            self::SQLITE_CREATE_TABLE_HEADER_PATTERN,
            '$1'.$quotedTmp.'$3',
            $createSql,
            1
        );

        $tmpCreate = preg_replace(
            self::SQLITE_DATA_COLUMN_PATTERN,
            ' "data_serialized"',
            $tmpCreate,
            1
        );

        $connection->executeStatement($tmpCreate);

        $cols = $connection->fetchAllAssociative(sprintf('PRAGMA table_info(%s)', $table));
        $colList = implode(', ', array_map(
            static fn (array $c): string => $platform->quoteSingleIdentifier($c['name']),
            $cols
        ));
        $connection->executeStatement(sprintf('INSERT INTO %s SELECT %s FROM %s', $quotedTmp, $colList, $quotedTable));

        $connection->executeStatement(sprintf('DROP TABLE %s', $quotedTable));
        $connection->executeStatement(sprintf('ALTER TABLE %s RENAME TO %s', $quotedTmp, $quotedTable));

        $clobType = $platform->getClobTypeDeclarationSQL([]);
        $connection->executeStatement(sprintf(
            'ALTER TABLE %s ADD COLUMN %s %s DEFAULT NULL',
            $quotedTable,
            $platform->quoteSingleIdentifier('data'),
            $clobType
        ));

        $qData = $platform->quoteSingleIdentifier('data');
        $qDataSerialized = $platform->quoteSingleIdentifier('data_serialized');
        $qId = $platform->quoteSingleIdentifier('id');

        $rows = $connection->fetchAllAssociative(
            sprintf('SELECT %s, %s FROM %s WHERE %s IS NOT NULL', $qId, $qDataSerialized, $quotedTable, $qDataSerialized)
        );
        $converted = 0;
        foreach ($rows as $row) {
            [$ok, $deserialized] = $this->safeUnserialize($row['data_serialized']);
            if ($ok) {
                try {
                    $json = json_encode($deserialized, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                    $connection->executeStatement(
                        sprintf('UPDATE %s SET %s = ? WHERE %s = ?', $quotedTable, $qData, $qId),
                        [$json, $row['id']]
                    );
                    ++$converted;
                } catch (\JsonException) {
                    $output->writeln(sprintf('  <comment>Warning: could not JSON-encode row id=%s – skipping.</comment>', $row['id']));
                }
            } else {
                $output->writeln(sprintf('  <comment>Warning: could not unserialize row id=%s – skipping.</comment>', $row['id']));
            }
        }

        $output->writeln(sprintf('  SQLite migration complete (%d row(s) converted).', $converted));
    }

    private function finalize(
        Connection $connection,
        AbstractPlatform $platform,
        string $table,
        string $quotedTable,
        bool $dropLegacy,
        OutputInterface $output
    ): void {
        if ($dropLegacy) {
            $output->writeln("Dropping legacy column 'data_serialized'...");
            $connection->executeStatement(sprintf(
                'ALTER TABLE %s DROP COLUMN %s',
                $quotedTable,
                $platform->quoteSingleIdentifier('data_serialized')
            ));
            $output->writeln('  Done.');

            return;
        }

        $output->writeln('');
        $output->writeln('Migration complete.');
        $output->writeln("The original data is still available in the 'data_serialized' column.");
        $output->writeln('Once you have verified the migration, you can drop it:');
        $output->writeln(sprintf('  ALTER TABLE %s DROP COLUMN data_serialized;', $table));
    }

    /**
     * @return array{0: bool, 1: mixed}
     */
    private function safeUnserialize(string $serialized): array
    {
        set_error_handler(static function (): bool {
            return true;
        });

        try {
            $deserialized = unserialize($serialized, ['allowed_classes' => false]);
        } catch (\Throwable) {
            restore_error_handler();

            return [false, null];
        }

        restore_error_handler();

        if (false === $deserialized && 'b:0;' !== $serialized) {
            return [false, null];
        }

        return [true, $deserialized];
    }
}
