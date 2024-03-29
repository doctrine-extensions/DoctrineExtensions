<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Mapping\Driver\ORM\XmlDriver;
use Gedmo\Tests\Mapping\Fixture\Xml\User;
use Gedmo\Tests\Sluggable\Fixture\Article as ArticleEntity;
use Gedmo\Tests\Sluggable\Fixture\Document\Article as ArticleDocument;
use Gedmo\Tests\Tool\BaseTestCaseOM;
use Gedmo\Tests\Translatable\Fixture\PersonTranslation;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MultiManagerMappingTest extends BaseTestCaseOM
{
    private EntityManager $em1;

    private EntityManager $em2;

    private DocumentManager $dm1;

    protected function setUp(): void
    {
        parent::setUp();

        // EM with standard annotation/attribute mapping
        $this->em1 = $this->getDefaultMockSqliteEntityManager([
            ArticleEntity::class,
        ]);

        // EM with XML and annotation/attribute mapping
        $annotationDriver = new AttributeDriver([]);

        $annotationDriver2 = new AttributeDriver([]);

        $xmlDriver = new XmlDriver(__DIR__.'/Driver/Xml');

        $chain = new MappingDriverChain();
        $chain->addDriver($annotationDriver, 'Gedmo\Tests\Translatable\Fixture');
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');
        $chain->addDriver($annotationDriver2, 'Gedmo\Translatable');

        $this->em2 = $this->getDefaultMockSqliteEntityManager([
            PersonTranslation::class,
            User::class,
        ], $chain);

        // DM with standard annotation/attribute mapping
        $this->dm1 = $this->getMockDocumentManager('gedmo_extensions_test');
    }

    public function testTwoDifferentManagers(): void
    {
        // Force metadata class loading.
        $this->dm1->getClassMetadata(ArticleDocument::class);
        $dmArticle = new ArticleDocument();
        $dmArticle->setCode('code');
        $dmArticle->setTitle('title');
        $this->dm1->persist($dmArticle);
        $this->dm1->flush();

        static::assertSame('title-code', $dmArticle->getSlug());
        $em1Article = new ArticleEntity();
        $em1Article->setCode('code');
        $em1Article->setTitle('title');
        $this->em1->persist($em1Article);
        $this->em1->flush();

        static::assertSame('title-code', $em1Article->getSlug());
    }

    public function testTwoSameManagers(): void
    {
        $em1Article = new ArticleEntity();
        $em1Article->setCode('code');
        $em1Article->setTitle('title');
        $this->em1->persist($em1Article);
        $this->em1->flush();

        static::assertSame('title-code', $em1Article->getSlug());

        $user = new User();
        $user->setUsername('user');
        $user->setPassword('secret');
        $this->em2->persist($user);
        $this->em2->flush();

        static::assertSame(1, $user->getId());
    }
}
