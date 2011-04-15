<?php

namespace Sluggable\Fixture;

/**
 * @Entity
 */
class Position
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private $id;

    /**
     * @gedmo:Sluggable(position=2)
     * @Column(length=16)
     */
    private $prop;

    /**
     * @gedmo:Sluggable(position=1)
     * @Column(length=64)
     */
    private $title;

    /**
     * @gedmo:Sluggable
     * @Column(length=16)
     */
    private $code;

    /**
     * @gedmo:Sluggable(position=0)
     * @Column(length=16)
     */
    private $other;

    /**
     * @gedmo:Slug
     * @Column(length=64, unique=true)
     */
    private $slug;
}