<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

        static::assertCount(5, $serializedWithoutGroups);
        static::assertNotSame($serializedWithGlobalGroups, $serializedWithoutGroups);

        static::assertCount(2, $serializedWithGlobalGroups);
        static::assertArrayHasKey('deletedAt', $serializedWithGlobalGroups);
        static::assertArrayHasKey('deleted', $serializedWithGlobalGroups);

        static::assertSame($serializedWithGlobalGroups, $serializedWithTraitGroups);
    }
}
