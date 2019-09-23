<?php

namespace Gedmo;

if (class_exists('\Doctrine\ODM\MongoDB\Repository\DocumentRepository')) {
    class MiddleManDocumentRepository extends \Doctrine\ODM\MongoDB\Repository\DocumentRepository {}
} else {
    class MiddleManDocumentRepository extends \Doctrine\ODM\MongoDB\DocumentRepository {}
}