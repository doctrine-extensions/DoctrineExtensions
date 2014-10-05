<?php

namespace Gedmo\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Tool\BaseTestCaseOM;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MultiManagerMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em1;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em2;

    /**
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm1;

    public function setUp()
    {
        parent::setUp();
        // EM with standard annotation mapping
        $this->em1 = $this->getMockSqliteEntityManager(array(
            'Sluggable\Fixture\Article',
        ));
        // EM with yaml and annotation mapping
        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $reader = new AnnotationReader();
        $annotationDriver2 = new AnnotationDriver($reader);

        $yamlDriver = new YamlDriver(__DIR__.'/Driver/Yaml');

        $chain = new DriverChain();
        $chain->addDriver($annotationDriver, 'Translatable\Fixture');
        $chain->addDriver($yamlDriver, 'Mapping\Fixture\Yaml');
        $chain->addDriver($annotationDriver2, 'Gedmo\Translatable');

        $this->em2 = $this->getMockSqliteEntityManager(array(
            'Translatable\Fixture\PersonTranslation',
            'Mapping\Fixture\Yaml\User',
        ), $chain);
        // DM with standard annotation mapping
        $this->dm1 = $this->getMockDocumentManager('gedmo_extensions_test');
    }

    public function testTwoDiferentManager()
    {
        $meta = $this->dm1->getClassMetadata('Sluggable\Fixture\Document\Article');
        $dmArticle = new \Sluggable\Fixture\Document\Article();
        $dmArticle->setCode('code');
        $dmArticle->setTitle('title');
        $this->dm1->persist($dmArticle);
        $this->dm1->flush();

        $this->assertEquals('title-code', $dmArticle->getSlug());
        $em1Article = new \Sluggable\Fixture\Article();
        $em1Article->setCode('code');
        $em1Article->setTitle('title');
        $this->em1->persist($em1Article);
        $this->em1->flush();

        $this->assertEquals('title-code', $em1Article->getSlug());
    }

    public function testTwoSameManagers()
    {
        $em1Article = new \Sluggable\Fixture\Article();
        $em1Article->setCode('code');
        $em1Article->setTitle('title');
        $this->em1->persist($em1Article);
        $this->em1->flush();

        $this->assertEquals('title-code', $em1Article->getSlug());

        $user = new \Mapping\Fixture\Yaml\User();
        $user->setUsername('user');
        $user->setPassword('secret');
        $this->em2->persist($user);
        $this->em2->flush();

        $this->assertEquals(1, $user->getId());
    }
}
