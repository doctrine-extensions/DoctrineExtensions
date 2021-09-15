<?php

namespace Gedmo\Translator\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Gedmo\Translator\Translation as BaseTranslation;

/**
 * Entity translation class.
 *
 * @author  Konstantin Kudryashov <ever.zet@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @MappedSuperclass
 */
#[MappedSuperclass]
abstract class Translation extends BaseTranslation
{
    /**
     * @var int
     *
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    #[Column(type: Types::INTEGER)]
    #[Id]
    #[GeneratedValue]
    protected $id;

    /**
     * @var string
     *
     * @Column(type="string", length=8)
     */
    #[Column(type: Types::STRING, length: 8)]
    protected $locale;

    /**
     * @var string
     *
     * @Column(type="string", length=32)
     */
    #[Column(type: Types::STRING, length: 32)]
    protected $property;

    /**
     * @var string
     *
     * @Column(type="text", nullable=true)
     */
    #[Column(type: Types::TEXT, nullable: true)]
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
