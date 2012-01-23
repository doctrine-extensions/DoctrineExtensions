<?php

use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslationListener;

$executionStart = microtime(true);
$memoryStart = memory_get_usage(true);

$em = include 'em.php';

/**
 * initialized in em.php
 *
 * Gedmo\Translatable\TranslationListener
 */
$translatable;

$repository = $em->getRepository('Entity\Category');
$food = $repository->findOneByTitle('Food');
if (!$food) {
    // lets create some categories
    $food = new Entity\Category;
    $food->setTitle('Food');

    $fruits = new Entity\Category;
    $fruits->setParent($food);
    $fruits->setTitle('Fruits');

    $apple = new Entity\Category;
    $apple->setParent($fruits);
    $apple->setTitle('Apple');

    $milk = new Entity\Category;
    $milk->setParent($food);
    $milk->setTitle('Milk');

    $em->persist($food);
    $em->persist($milk);
    $em->persist($fruits);
    $em->persist($apple);
    $em->flush();

    // translate into LT
    $translatable->setTranslatableLocale('lt');
    $food->setTitle('Maistas');
    $fruits->setTitle('Vaisiai');
    $apple->setTitle('Obuolys');
    $milk->setTitle('Pienas');

    $em->persist($food);
    $em->persist($milk);
    $em->persist($fruits);
    $em->persist($apple);
    $em->flush();
    // set locale back to en
    $translatable->setTranslatableLocale('en');
}

// create query to fetch tree nodes
$query = $em
    ->createQueryBuilder()
    ->select('node')
    ->from('Entity\Category', 'node')
    ->orderBy('node.root, node.lft', 'ASC')
    ->getQuery()
;
// set hint to translate nodes
$query->setHint(
    Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
    'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
);
$treeDecorationOptions = array(
    'decorate' => true,
    'rootOpen' => '',
    'rootClose' => '',
    'childOpen' => '',
    'childClose' => '',
    'nodeDecorator' => function($node) {
        return str_repeat('-', $node['level']).$node['title'].PHP_EOL;
    }
);
// build tree in english
echo $repository->buildTree($query->getArrayResult(), $treeDecorationOptions).PHP_EOL.PHP_EOL;
// change locale
$query->setHint(TranslationListener::HINT_TRANSLATABLE_LOCALE, 'lt');
// build tree in lithuanian
echo $repository->buildTree($query->getArrayResult(), $treeDecorationOptions).PHP_EOL.PHP_EOL;

$ms = round(microtime(true) - $executionStart, 4) * 1000;
$mem = round((memory_get_usage(true) - $memoryStart) / 1000000, 2);
echo "Execution took: {$ms} ms, memory consumed: {$mem} Mb";
