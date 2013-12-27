<?php

namespace Sluggable\Fixture\Issue939;

use Gedmo\Sluggable\SluggableListener as BaseSluggableListener;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Sluggable\Fixture\Issue939\Category;

class SluggableListener extends BaseSluggableListener
{
    protected $originalTransliterator;
    protected $originalUrlizer;

    public function __construct()
    {
        $this->originalTransliterator = $this->getTransliterator();
        $this->originalUrlizer = $this->getUrlizer();

        $this->setTransliterator(array($this, 'transliterator'));
        $this->setUrlizer(array($this, 'urlizer'));
    }

    public function transliterator($slug, $separator = '-', $object)
    {
        if ($object instanceof Article) {
            // custom transliteration here
            return $slug;
        }

        return call_user_func_array(
            $this->originalTransliterator,
            array($slug, $separator, $object)
        );
    }

    public function urlizer($slug, $separator = '-', $object)
    {
        if ($object instanceof Article) {
            // custom urlization here
            return $slug;
        }

        return call_user_func_array(
            $this->originalUrlizer,
            array($slug, $separator, $object)
        );
    }
}