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
use Doctrine\Common\EventManager;
use Doctrine\Deprecations\Deprecation;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Reflection\TypedNoDefaultReflectionPropertyBase;
use Gedmo\Sluggable\SluggableListener;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

final class MappingEventSubscriberTest extends ORMMappingTestCase
{
    use VerifyDeprecations;
    use ExpectDeprecationTrait;

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->getBasicConfiguration();

        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $this->em = EntityManager::create($conn, $config, new EventManager());
    }

    /**
     * @group legacy
     */
    public function testGetConfigurationCachedFromDoctrine(): void
    {
        // doctrine/persistence changed from trigger_error to doctrine/deprecations in 2.2.1. In 2.2.2 this trait was
        // added, this is used to know if the doctrine/persistence version is using trigger_error or
        // doctrine/deprecations. This "if" check can be removed once we drop support for doctrine/persistence < 2.2.1
        if (trait_exists(TypedNoDefaultReflectionPropertyBase::class)) {
            Deprecation::enableWithTriggerError();

            $this->expectDeprecationWithIdentifier('https://github.com/doctrine/persistence/issues/184');
        } else {
            $this->expectDeprecation('Doctrine\Persistence\Mapping\AbstractClassMetadataFactory::getCacheDriver is deprecated. Use getCache() instead.');
        }

        $subscriber = new SluggableListener();
        $subscriber->getExtensionMetadataFactory($this->em);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [];
    }
}
