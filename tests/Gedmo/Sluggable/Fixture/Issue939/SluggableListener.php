<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Fixture\Issue939;

use Gedmo\Sluggable\SluggableListener as BaseSluggableListener;

final class SluggableListener extends BaseSluggableListener
{
    /**
     * @var callable(string, string, object=): string
     */
    protected $originalTransliterator;

    /**
     * @var callable(string, string, object=): string
     */
    protected $originalUrlizer;

    public function __construct()
    {
        parent::__construct();

        $this->originalTransliterator = $this->getTransliterator();
        $this->originalUrlizer = $this->getUrlizer();

        $this->setTransliterator([$this, 'transliterator']);
        $this->setUrlizer([$this, 'urlizer']);
    }

    public function transliterator(string $slug, string $separator = '-', ?object $object = null): string
    {
        if ($object instanceof Article) {
            // custom transliteration here
            return $slug;
        }

        $originalTransliterator = $this->originalTransliterator;

        return $originalTransliterator($slug, $separator, $object);
    }

    public function urlizer(string $slug, string $separator = '-', ?object $object = null): string
    {
        if ($object instanceof Article) {
            // custom urlization here
            return $slug;
        }

        $originalUrlizer = $this->originalUrlizer;

        return $originalUrlizer($slug, $separator, $object);
    }
}
