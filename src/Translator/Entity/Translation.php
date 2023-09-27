<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translator\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translator\Translation as BaseTranslation;

/**
 * Entity translation class.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @ORM\MappedSuperclass
 */
#[ORM\MappedSuperclass]
abstract class Translation extends BaseTranslation
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=8)
     */
    #[ORM\Column(type: Types::STRING, length: 8)]
    protected $locale;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=32)
     */
    #[ORM\Column(type: Types::STRING, length: 32)]
    protected $property;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected $value;

    /**
     * Get id
     *
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }
}
