<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Gedmo\Tests\Timestampable\Fixture\UsingTrait;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Timestampable\TimestampableListener;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * These are tests for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TraitUsageTest extends BaseTestCaseORM
{
    public const TARGET = UsingTrait::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldTimestampUsingTrait(): void
    {
        $sport = new UsingTrait();
        $sport->setTitle('Sport');

        $this->em->persist($sport);
        $this->em->flush();

        static::assertNotNull($sport->getCreatedAt());
        static::assertNotNull($sport->getUpdatedAt());
    }

    public function testTraitMethodthShouldReturnObject(): void
    {
        $sport = new UsingTrait();
        static::assertInstanceOf(UsingTrait::class, $sport->setCreatedAt(new \DateTime()));
        static::assertInstanceOf(UsingTrait::class, $sport->setUpdatedAt(new \DateTime()));
    }

    public function testShouldBeSerializedUsingTrait(): void
    {
        $person = new UsingTrait();

        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new ObjectNormalizer($classMetadataFactory)];
        $serializer = new Serializer($normalizers);

        $serializedWithGlobalGroups = $serializer->normalize($person, null, ['groups' => 'gedmo.doctrine_extentions.traits']);
        $serializedWithTraitGroups = $serializer->normalize($person, null, ['groups' => 'gedmo.doctrine_extentions.trait.timestampable']);
        $serializedWithoutGroups = $serializer->normalize($person);

        self::assertCount(4, $serializedWithoutGroups);
        self::assertNotEquals($serializedWithGlobalGroups, $serializedWithoutGroups);

        self::assertCount(2, $serializedWithGlobalGroups);
        self::assertArrayHasKey('createdAt', $serializedWithGlobalGroups);
        self::assertArrayHasKey('updatedAt', $serializedWithGlobalGroups);

        self::assertEquals($serializedWithGlobalGroups, $serializedWithTraitGroups);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }
}
