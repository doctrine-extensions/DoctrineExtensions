<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Tests\Mapping\Fixture\Yaml\User;
use Gedmo\Tests\Sluggable\Fixture\Document\Article;
use Gedmo\Tests\Tool\BaseTestCaseOM;
use Gedmo\Tests\Translatable\Fixture\PersonTranslation;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MultiManagerMappingTest extends BaseTestCaseOM
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em1;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em2;

    /**
     * @var \Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm1;

    protected function setUp(): void
    {
        parent::setUp();
        // EM with standard annotation mapping
        $this->em1 = $this->getDefaultMockSqliteEntityManager([
            \Gedmo\Tests\Sluggable\Fixture\Article::class,
        ]);
        // EM with yaml and annotation mapping
        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $reader = new AnnotationReader();
        $annotationDriver2 = new AnnotationDriver($reader);

        $yamlDriver = new YamlDriver(__DIR__.'/Driver/Yaml');

        $chain = new MappingDriverChain();
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Translatable\Fixture');
        $chain->addDriver($yamlDriver, 'Gedmo\Tests\Mapping\Fixture\Yaml');
        $chain->addDriver($annotationDriver2, 'Gedmo\Translatable');

        $this->em2 = $this->getDefaultMockSqliteEntityManager([
            PersonTranslation::class,
            User::class,
        ], $chain);
        // DM with standard annotation mapping
        $this->dm1 = $this->getMockDocumentManager('gedmo_extensions_test');
    }

    public function testTwoDifferentManagers(): void
    {
        $meta = $this->dm1->getClassMetadata(Article::class);
        $dmArticle = new \Gedmo\Tests\Sluggable\Fixture\Document\Article();
        $dmArticle->setCode('code');
        $dmArticle->setTitle('title');
        $this->dm1->persist($dmArticle);
        $this->dm1->flush();

        static::assertSame('title-code', $dmArticle->getSlug());
        $em1Article = new \Gedmo\Tests\Sluggable\Fixture\Article();
        $em1Article->setCode('code');
        $em1Article->setTitle('title');
        $this->em1->persist($em1Article);
        $this->em1->flush();

        static::assertSame('title-code', $em1Article->getSlug());
    }

    public function testTwoSameManagers(): void
    {
        $em1Article = new \Gedmo\Tests\Sluggable\Fixture\Article();
        $em1Article->setCode('code');
        $em1Article->setTitle('title');
        $this->em1->persist($em1Article);
        $this->em1->flush();

        static::assertSame('title-code', $em1Article->getSlug());

        $user = new \Gedmo\Tests\Mapping\Fixture\Yaml\User();
        $user->setUsername('user');
        $user->setPassword('secret');
        $this->em2->persist($user);
        $this->em2->flush();

        static::assertSame(1, $user->getId());
    }
}
