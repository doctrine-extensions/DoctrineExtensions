<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable;

use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Document\Article;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for Blameable behavior, when no user is available
 *
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
final class NoUserTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new BlameableListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        // create the document manager
        $this->getDefaultDocumentManager($evm);
    }

    public function testWhenNoUserIsAvailable(): void
    {
        $sport = new Article();
        $sport->setTitle('sport no user');

        $this->dm->persist($sport);
        $this->dm->flush();
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::ARTICLE);
        $sport = $repo->findOneBy(['title' => 'sport no user']);
        static::assertEmpty($sport->getCreated());
        static::assertEmpty($sport->getUpdated());
    }
}
