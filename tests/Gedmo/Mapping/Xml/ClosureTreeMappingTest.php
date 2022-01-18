<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Tests\Mapping\Fixture\ClosureTreeClosure;
use Gedmo\Tests\Mapping\Fixture\Xml\ClosureTree;
use Gedmo\Tests\Tool\BaseTestCaseOM;
use Gedmo\Tree\TreeListener;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ClosureTreeMappingTest extends BaseTestCaseOM
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var TreeListener
     */
    private $tree;

    protected function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new MappingDriverChain();
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Mapping\Fixture');
        $chain->addDriver($annotationDriver, 'Gedmo\Tree');

        $this->tree = new TreeListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->tree);

        $this->em = $this->getDefaultMockSqliteEntityManager([
            ClosureTree::class,
            ClosureTreeClosure::class,
        ], $chain);
    }

    public function testTreeMetadata(): void
    {
        $meta = $this->em->getClassMetadata(ClosureTree::class);
        $config = $this->tree->getConfiguration($this->em, $meta->getName());

        static::assertArrayHasKey('strategy', $config);
        static::assertSame('closure', $config['strategy']);
        static::assertArrayHasKey('closure', $config);
        static::assertSame(ClosureTreeClosure::class, $config['closure']);
        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
    }
}
