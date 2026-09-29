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
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Tests\Blameable\Fixture\Entity\CompanyInvalidAssociation;
use Gedmo\Tests\Blameable\Fixture\Entity\CompanyInvalidFieldType;
use Gedmo\Tests\Blameable\Fixture\Entity\Type;
use Gedmo\Tests\Tool\BaseTestCaseORM;

final class BlameableInvalidMappingTest extends BaseTestCaseORM
{
    private EventManager $evm;

    /** @var list<class-string> */
    private array $fixtures = [];

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new BlameableListener();
        $listener->setUserValue('testuser');

        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($listener);
    }

    public function testBlameableRejectsUnsupportedScalarFieldType(): void
    {
        $this->fixtures = [CompanyInvalidFieldType::class];

        $this->expectException(InvalidMappingException::class);
        $this->expectExceptionMessage("must be 'string', 'integer', 'bigint', 'smallint' or a one-to-many relation");

        $this->getDefaultMockSqliteEntityManager($this->evm);
    }

    public function testBlameableRejectsMultiValuedAssociation(): void
    {
        $this->fixtures = [
            CompanyInvalidAssociation::class,
            Type::class,
        ];

        $this->expectException(InvalidMappingException::class);
        $this->expectExceptionMessage('must be a one-to-many relation or a string or integer or bigint or smallint field');

        $this->getDefaultMockSqliteEntityManager($this->evm);
    }

    protected function getUsedEntityFixtures(): array
    {
        return $this->fixtures;
    }
}
