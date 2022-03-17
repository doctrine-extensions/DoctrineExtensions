<?php

namespace Gedmo\Tests\SoftDeleteable;

use Doctrine\Common\Annotations\AnnotationReader;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\PersonUsingTrait;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SoftDeletableTraitSerializationTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldBeSerializedUsingTrait(): void
    {
        $person = new PersonUsingTrait();

        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [new ObjectNormalizer($classMetadataFactory)];
        $serializer = new Serializer($normalizers);

        $serializedWithGlobalGroups = $serializer->normalize($person, null, ['groups' => 'gedmo.doctrine_extentions.traits']);
        $serializedWithTraitGroups = $serializer->normalize($person, null, ['groups' => 'gedmo.doctrine_extentions.trait.soft_deleteable']);
        $serializedWithoutGroups = $serializer->normalize($person);

        self::assertCount(5, $serializedWithoutGroups);
        self::assertNotEquals($serializedWithGlobalGroups, $serializedWithoutGroups);

        self::assertCount(2, $serializedWithGlobalGroups);
        self::assertArrayHasKey('deletedAt', $serializedWithGlobalGroups);
        self::assertArrayHasKey('deleted', $serializedWithGlobalGroups);

        self::assertEquals($serializedWithGlobalGroups, $serializedWithTraitGroups);
    }
}