### Making safe transactions in concurrent environment

Some extensions are using atomic updates, which need some extra attention
in order to maintain data integrity. If you are using one of these extensions
listed below, you should read further and take the appropriate actions:

- Sortable
- Tree - NestedSet strategy
- Tree - MaterializedPath strategy

So let me explain first, why and what actions are needed to be applied to
maintain your data integrity.

Imagine two concurrent requests are being issued with some entity updates which does
some actions for one or more of these extensions listed. One request starts a transaction
to do atomic updates and another at the same time, while the first transaction executes
starts the second transaction. The second transaction might be performing updates based
on data which is outdated or even still running in the first transaction. The possibility
to have broken data increases with concurrency.

We need to lock one transaction so the other would wait until the first finishes and then we can
begin the second one which in turn would lock the third one if there would be any.

**NOTE:** it is not enough to simply have a transaction.

So how we can achieve this? The simplest solution is [pessimistic locking](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/transactions-and-concurrency.html#pessimistic-locking) which is supported by ORM.

So how we can use it correctly to maintain our transactions safe from one another. Lets say we have two entity types in
our application:

- **Shop** - lets say our ecommerce platform we are creating, supports multiple shops.
- **Category** - every shop might have a different category set for products and other features.

So the **Category** should be a **nested set tree** strategy based, where atomic updates might be executed
if a category is being moved, inserted or removed.

To start with, I'll make the simple definitions of these two entities:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Shop
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(length=64)
     */
    private $name;

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }
}
```

It should have owner and so many more attributes, but lets keep it simple. Here follows Category:

```php
<?php

namespace App\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class Category
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(length=64)
     */
    private $title;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer")
     */
    private $rgt;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\ManyToOne(targetEntity="Shop")
     */
    private $shop;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(type="integer", nullable=true)
     */
    private $root;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    private $level;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setParent(Category $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setShop(Shop $shop)
    {
        $this->shop = $shop;
        return $this;
    }

    public function getShop()
    {
        return $this->shop;
    }
}
```

**NOTE:** it would be perfect if we could use tree root as a shop relation. But it is not currently supported and
might be available only in next versions.

Now everytime we do **insert**, **move** or **remove** actions for Category:

```php
<?php

use Doctrine\DBAL\LockMode;

class CategoryController extends Controller
{
    function postCategoryAction($currentShopId)
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();
        $categoryRepository = $em->getRepository("App\Entity\Category");
        // start transaction
        $conn->beginTransaction();
        try {
            // select shop for update - locks it for any read attempts until this transaction ends
            $shop = $em->find("App\Entity\Shop", $currentShopId, LockMode::PESSIMISTIC_WRITE);

            // create a new category
            $category = new Category;
            $category->setTitle($_POST["title"]);
            $category->setShop($shop);
            $parent = $categoryRepository->findOneById($_POST["parent_id"]);

            // persist and flush
            $categoryRepository->persistAsFirstChildOf($category, $parent);
            $em->flush();

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

        // if all went well, we can set flash message or whatever
        // other operations which attempts to select in lock mode, will wait till this transaction ends.
    }
}
```

You may separate locking transaction to run callback function and make it as a service to abstract and prevent
code duplication. Anyway, my advice would be to use only one transaction per request and best inside controller
directly, where you would ensure that all operations performed during the action can be safely rolled back.

Also to use this kind of locking, you need an entity which is necessary to read on concurrent request which attempts
to update the same tree. In this example, **Shop** entity fits the bill perfectly. Otherwise you need to find a way to
safely lock the tree table.

The point of this example is: that concurrently atomic updates, might cause other parallel actions to use outdated
information, based on which it may perform falsely calculated consequent updates. And you need to prevent this from
happening in order to maintain your data. Extensions and ORM cannot perform such actions automatically.

