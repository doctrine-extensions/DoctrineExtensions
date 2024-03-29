<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Tree\Fixture\BehavioralCategory;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Tree\TreeListener;
use PHPUnit\Framework\TestCase;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MappingTest extends TestCase
{
    private const TEST_ENTITY_CATEGORY = BehavioralCategory::class;
    private const TEST_ENTITY_TRANSLATION = Translation::class;

    private EntityManager $em;

    private TimestampableListener $timestampable;

    protected function setUp(): void
    {
        $config = new Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $config->setMetadataDriverImpl(new AttributeDriver([]));

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $evm = new EventManager();
        $evm->addEventSubscriber(new TranslatableListener());
        $this->timestampable = new TimestampableListener();
        $evm->addEventSubscriber($this->timestampable);
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber(new TreeListener());
        $this->em = new EntityManager(DriverManager::getConnection($conn, $config), $config, $evm);

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema([
            $this->em->getClassMetadata(self::TEST_ENTITY_CATEGORY),
            $this->em->getClassMetadata(self::TEST_ENTITY_TRANSLATION),
        ]);
    }

    public function testNoCacheImplementationMapping(): void
    {
        $food = new BehavioralCategory();
        $food->setTitle('Food');
        $this->em->persist($food);
        $this->em->flush();
        // assertion checks if configuration is read correctly without cache driver
        $conf = $this->timestampable->getConfiguration(
            $this->em,
            self::TEST_ENTITY_CATEGORY
        );
        static::assertCount(0, $conf);
    }
}
