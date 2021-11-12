<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Gedmo\Tests\Tree\Fixture\BehavioralCategory;
use Gedmo\Translatable\Entity\Translation;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MappingTest extends \PHPUnit\Framework\TestCase
{
    public const TEST_ENTITY_CATEGORY = BehavioralCategory::class;
    public const TEST_ENTITY_TRANSLATION = Translation::class;

    private $em;
    private $timestampable;

    protected function setUp(): void
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        //$this->markTestSkipped('Skipping according to a bug in annotation reader creation.');
        $config->setMetadataDriverImpl(new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($_ENV['annotation_reader']));

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new \Gedmo\Translatable\TranslatableListener());
        $this->timestampable = new \Gedmo\Timestampable\TimestampableListener();
        $evm->addEventSubscriber($this->timestampable);
        $evm->addEventSubscriber(new \Gedmo\Sluggable\SluggableListener());
        $evm->addEventSubscriber(new \Gedmo\Tree\TreeListener());
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema([
            $this->em->getClassMetadata(self::TEST_ENTITY_CATEGORY),
            $this->em->getClassMetadata(self::TEST_ENTITY_TRANSLATION),
        ]);
    }

    public function testNoCacheImplementationMapping()
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
