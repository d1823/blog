[//]: # (TITLE: Avoiding Pitfalls with Doctrine ORM: The Impact of Type Hints)
[//]: # (DESCRIPTION: How incorrect type hints can affect the performance of your persistence layer.)
[//]: # (DATE: 2024-03-23)
[//]: # (TAGS: php, doctrine, entity manager, unit of work)

Doctrine tends to be a pretty forgiving ORM. Despite it's rigid structure and reliance on complex class hierarchies, it has a bunch
of cases that it happily lets slide. For some, it will emit a warning (ex. incorrectly defined relationships). Others will be silently
accepted without too much of a hassle, aside from a note in the documentation (ex. [nested flushes through event
listeners](https://www.doctrine-project.org/projects/doctrine-orm/en/3.1/reference/events.html#events-overview)). Recently, I discovered an
edge case that doesn't generate any warnings, and - as far as I know - is not mentioned by the documentation. When the type specified by
a column definition doesn't match the type of the underlying property, Doctrine happily detects non existing changes. Why does it even
matter? Read to the end.

### An example

Here's a bare-bones Doctrine entity.

```php
<?php

#[ORM\Entity]
class Book
{
    #[Id]
    #[GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\Column(type: 'decimal')]
    public float $price;
}
```

There's nothing special about it aside from the types visible on the *price* property. The column is
defined as a **decimal**, while the property's type-hint is **float**. Let's boot Doctrine and see what it has to say about it.

```sh
$ bin/console doctrine:mapping:info
 Found 1 mapped entities:

 [OK]   App\Entity\Book
```

Well... not much. That's despite the column definition clearly being incorrect. Let's try to use it.

```php
<?php
class DatabaseTest extends KernelTestCase
{
    public function testDoctrine(): void
    {
        $em = $this->getContainer()->get(EntityManagerInterface::class);

        $book = new Book();
        $book->price = 5.0;

        $em->persist($book);
        $em->flush();

        $this->assertIsInt($book->id);
    }
}
```

It ends up working.

```sh
Time: 00:00.128, Memory: 18.00 MB

OK (1 test, 1 assertion)
```

Huh. Nothing out of ordinary. Let's try with a fresh Entity Manager, and fetch the persisted entity. Let's also emit an immediate flush...

```php
<?php

class DatabaseTest extends KernelTestCase
{
    public function testDoctrine(): void
    {
        ...

        $em->clear();

        $em->find(Book::class, $book->id);
        $em->flush();
    }
}
```

...and inspect the statements Doctrine has issued in the background.

```log
Beginning transaction [] []
Executing statement: INSERT INTO book (price) VALUES (?) (parameters: array{"1":5.0}, types: array{"1":2}) {"sql":"INSERT INTO book (price) VALUES (?)","params":{"1":5.0},"types":{"1":2}} []
Committing transaction [] []
Executing statement: SELECT t0.id AS id_1, t0.price AS price_2 FROM book t0 WHERE t0.id = ? (parameters: array{"1":8}, types: array{"1":1}) {"sql":"SELECT t0.id AS id_1, t0.price AS price_2 FROM book t0 WHERE t0.id = ?","params":{"1":8},"types":{"1":1}} []
Beginning transaction [] []
Executing statement: UPDATE book SET price = ? WHERE id = ? (parameters: array{"1":5.0,"2":8}, types: array{"1":2,"2":1}) {"sql":"UPDATE book SET price = ? WHERE id = ?","params":{"1":5.0,"2":8},"types":{"1":2,"2":1}} []
Committing transaction [] []
```

We've got the INSERT statement corresponding to the initial flush with the new **Book** entity, there's also the SELECT query we've executed
when we called `EntityManager::find`, then there's... an UPDATE statement despite the fact that we did not make any updates to the price.
What gives?

### Unit of Work & change detection

When you're using Doctrine's ORM, everything you do is going through the Entity Manager. Each inserted, updated or fetched entity is
recorded in an identity map so that the Unit of Work can keep track of them throughout their life cycle. At this point it's considered a
common knowledge and is definitely better explained in Doctrine's documentation. The only reason why I'm mentioning it is because it's also
the reason why the logs above show an implicit UPDATE statement. The Unit of Work considers the book entity to be dirty. Why exactly?

When you execute a flush, the entity manager utilizes its internal Unit of Work to translate the state of entities into a set of different
statements. To do that, the Unit of Work needs to iterate over its identity map and compare the current state of entities with their
original state snapshotted right after they were fetched from the database. The snippet below represents the logic driving the process of
establishing whether a particular value has been changed, and you can clearly see that it uses an **identity comparison**.

```php
<?php

class UnitOfWork
{
    ...

    public function computeChangeSet(ClassMetadata $class, object $entity): void
    {
        ...

        // Entity is "fully" MANAGED: it was already fully persisted before
        // and we have a copy of the original data
        $originalData = $this->originalEntityData[$oid];
        $changeSet    = [];

        foreach ($actualData as $propName => $actualValue) {
            ...

            $orgValue = $originalData[$propName];

            ...

            // skip if value haven't changed
            if ($orgValue === $actualValue) {
                continue;
            }

        ...
    }
}
```

Because the identity comparison is described as "true if $a is equal to $b, and they are of the same type", the provided entity example
represents an entity with an invalid mapping. The mapping is invalid because the Unit of Work cannot properly determine whether a given
property has really changed.

### The impact & conclusions

Older codebases are full of entities like the example above. It certainly was the case for one of the projects I'm working on. At the
first glance, one additional almost-noop UPDATE statement might not seem like a big of a deal, but with enough entities like that and enough
traffic, it might became a real problem. Unrelated operations might become sources of updates to specific tables, increasing the number of
update locks with possibilities for deadlocks, as well as the amount of resources needed for the database to serve under an application,
increasing the operating costs.

How to avoid it? Doctrine offers us a few options:

- we can keep both the column & property types in sync
- we can drop the column type and let it be auto inferred based on the property type
- we can drop the property type and let it be auto inferred based on the column type

Obviously, each one has its set of pros and cons which means the answer is "It depends".

PS: Oh, and please don't use floats to store decimals. Refactoring an established codebase away from that architectural decision is a really
difficult task.
