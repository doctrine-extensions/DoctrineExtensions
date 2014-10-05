<?php

namespace Gedmo\Translator\Entity;

use Gedmo\Translator\Translation as BaseTranslation;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;

/**
 * Entity translation class.
 *
 * @author  Konstantin Kudryashov <ever.zet@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @MappedSuperclass
 */
abstract class Translation extends BaseTranslation
{
    /**
     * @var integer $id
     *
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var string $locale
     *
     * @Column(type="string", length=8)
     */
    protected $locale;

    /**
     * @var string $property
     *
     * @Column(type="string", length=32)
     */
    protected $property;

    /**
     * @var string $value
     *
     * @Column(type="text", nullable=true)
     */
    protected $value;

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }
}
