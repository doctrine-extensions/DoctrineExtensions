<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Entity\UsingTrait;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * These are tests for Blameable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TraitUsageTest extends BaseTestCaseORM
{
    public const TARGET = UsingTrait::class;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new BlameableListener();
        $listener->setUserValue('testuser');
        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldTimestampUsingTrait(): void
    {
        $sport = new UsingTrait();
        $sport->setTitle('Sport');

        $this->em->persist($sport);
        $this->em->flush();

        static::assertNotNull($sport->getCreatedBy());
        static::assertNotNull($sport->getUpdatedBy());
    }

    public function testShouldBeSerializedUsingTrait(): void
    {
        $sport = new UsingTrait();

        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new ObjectNormalizer($classMetadataFactory)];
        $serializer = new Serializer($normalizers);

        $serializedWithGlobalGroups = $serializer->normalize($sport, null, ['groups' => 'gedmo.doctrine_extentions.traits']);
        $serializedWithTraitGroups = $serializer->normalize($sport, null, ['groups' => 'gedmo.doctrine_extentions.trait.blameable']);
        $serializedWithoutGroups = $serializer->normalize($sport);

        self::assertCount(4, $serializedWithoutGroups);
        self::assertNotEquals($serializedWithGlobalGroups, $serializedWithoutGroups);

        self::assertCount(2, $serializedWithGlobalGroups);
        self::assertArrayHasKey('createdBy', $serializedWithGlobalGroups);
        self::assertArrayHasKey('updatedBy', $serializedWithGlobalGroups);

        self::assertEquals($serializedWithGlobalGroups, $serializedWithTraitGroups);
    }

    public function testTraitMethodthShouldReturnObject(): void
    {
        $sport = new UsingTrait();
        static::assertInstanceOf(self::TARGET, $sport->setCreatedBy('myuser'));
        static::assertInstanceOf(self::TARGET, $sport->setUpdatedBy('myuser'));
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }
}
