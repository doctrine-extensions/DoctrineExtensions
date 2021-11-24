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

class SluggableListener extends BaseSluggableListener
{
    protected $originalTransliterator;
    protected $originalUrlizer;

    public function __construct()
    {
        $this->originalTransliterator = $this->getTransliterator();
        $this->originalUrlizer = $this->getUrlizer();

        $this->setTransliterator([$this, 'transliterator']);
        $this->setUrlizer([$this, 'urlizer']);
    }

    public function transliterator($slug, $separator = '-', $object = null)
    {
        if ($object instanceof Article) {
            // custom transliteration here
            return $slug;
        }

        return call_user_func_array(
            $this->originalTransliterator,
            [$slug, $separator, $object]
        );
    }

    public function urlizer($slug, $separator = '-', $object = null)
    {
        if ($object instanceof Article) {
            // custom urlization here
            return $slug;
        }

        return call_user_func_array(
            $this->originalUrlizer,
            [$slug, $separator, $object]
        );
    }
}
