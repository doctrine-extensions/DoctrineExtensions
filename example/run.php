<?php

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

// builds tree
$build = function ($nodes) use (&$build, $repository) {
    $result = '';
    foreach ($nodes as $node) {
        $result .= str_repeat("-", $node->getLevel())
            . $node->getTitle()
            . '('.$node->getSlug().')'
            . PHP_EOL
        ;
        if ($repository->childCount($node, false)) {
            $result .= $build($repository->children($node, true));
        }
    }
    return $result;
};

$nodes = $repository->getRootNodes();
echo $build($nodes).PHP_EOL.PHP_EOL;
// change locale
$translatable->setTranslatableLocale('lt');
$nodes = $repository->getRootNodes(); // reload in diferent locale
echo $build($nodes).PHP_EOL.PHP_EOL;

$ms = round(microtime(true) - $executionStart, 4) * 1000;
$mem = round((memory_get_usage(true) - $memoryStart) / 1000000, 2);
echo "Execution took: {$ms} ms, memory consumed: {$mem} Mb";
