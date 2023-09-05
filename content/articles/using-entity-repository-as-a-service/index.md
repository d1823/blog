[//]: # (TITLE: Using Doctrine's EntityRepository as a service is a bad idea)
[//]: # (DESCRIPTION: Why Doctrine's ServiceEntityRepository, and not EntityRepository, should be only be ever used as a service)
[//]: # (DATE: 2023-01-20)
[//]: # (UPDATE DATE: 2023-09-05)
[//]: # (TAGS: php, symfony, entity manager, service repository)

If you're using Symfony, you've definitely written a Doctrine repository at some point. If you've been using Symfony long enough, you've probably seen the introduction of *ServiceEntityRepository* that complemented the default *EntityRepository* base class. Its initial goal was to ease the use of repositories with service containers, instead of being tied directly to Doctrine, but that's not what I want to talk about. I really want to talk about the implications of using a repository extending the *EntityRepository* rather than the *ServiceEntityRepository*.

### The issue

During a bug-fixing-technical-debt-reducing period in one of the projects I'm working on, I decided to take a look at a long-standing issue that was popping up in logs from time to time. It wasn't anything critical as the crash was happening in the context of Symfony's message consumer in the Messenger component, and the handler run often enough to eventually do what it was supposed to do. The exception we've been getting was "Entity manager is closed" thrown by Doctrine. It wasn't apparent why it was thrown in the first place, because none of the operations executed by Doctrine, before this exception, failed in any way.

I've spent hours verifying the database schema and various input sets while trying to reproduce the crash locally. Eventually, I decided to look at it in a broader context - the handler was executed by a long-running consumer. It became apparent that the reason for throwing that exception might've originated from a different part of the code that wasn't part of the executions paths under the affected handler. I looked at logs preceding the crash and noticed a pattern where it would only throw an exception if a previously consumed message resulted in the Doctrine exception as well.

I was on the right track. At this point, I knew that the entity manager was being closed because of an exception thrown by a different handler processing a previous message. One question remained - why wasn't Doctrine reset to its original state before consuming a new message? After all, Messenger handles it implicitly through the [*DoctrinePingConnectionMiddleware*](https://github.com/symfony/doctrine-bridge/blob/5.4/Messenger/DoctrinePingConnectionMiddleware.php) - it resets the manager before passing the execution to the rest of the middleware stack.

After a little more digging, I've found the issue was caused by the fact that the affected repository extended straight from the *EntityRepository*, not the *ServiceEntityRepository*. Fixing that one simple mistake made the error go away.

### But why?

To understand why the manager was not being reset in-between processing of different messages, we need to take a closer look at these two classes:

```php
class EntityRepository implements ObjectRepository, Selectable
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        $this->_entityName = $class->name;
        $this->_em         = $em;
        $this->_class      = $class;
    }
}
```

```php
class ServiceEntityRepository extends EntityRepository implements ServiceEntityRepositoryInterface
{
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        $manager = $registry->getManagerForClass($entityClass);

        if ($manager === null) {
            throw new LogicException(sprintf(
                'Could not find the entity manager for class "%s". Check your Doctrine configuration to make sure it is configured to load this entity’s metadata.',
                $entityClass
            ));
        }

        parent::__construct($manager, $manager->getClassMetadata($entityClass));
    }
}
```

We can immediately notice two things:

  - the *ServiceEntityRepository* is a child of the *EntityRepository*
  - in contrast to its parent, *ServiceEntityRepository* isn't constructed with an object implementing the *EntityManagerInterface*, but with an instance of *ManagerRegistry* instead

What's the difference?

When an *EntityManager* encounters a driver exception during the *flush* operation, it's going to close. You won't be able to use this instance anymore as there's no way to make it reopen. By extension, all instances of repositories using that exact instance of *EntityManager* will become useless.
You can, of course, reach the *ManagerRegistry* and call the *resetManager* method, but that won't fix anything, aside from giving you a fresh instance of *EntityManager*.

With *ServiceEntityRepository* it's a different story. Symfony implements its own *ManagerRegistry* that knows how to talk to a PSR's *ContainerInterface*, and surprisingly, the *EntityManager* returned by the method *ManagerRegistry::getManagerForClass* is actually a proxy!

When such *ManagerRegistry* is asked for a fresh *EntityManager*, it doesn't only construct a new one, **it updates the existing proxy with a fresh, connected instance**. That way, when you reset the manager as part of your exception-handling procedure, this change is propagated throughout the system. **It means that in my case, the direct reason of an exception being thrown was a stale reference to the closed entity manager.**

We can further confirm that theory by taking a look at service definitions for both the *EntityManager* and the *EntityManagerInterface*. The first one is found in Doctrine's [DependencyInjection](https://github.com/doctrine/DoctrineBundle/blob/2.8.x/DependencyInjection/DoctrineExtension.php#L666-L675) that sets up the service named *doctrine.orm.default_entity_manager*. The second one is a debug output from the container itself when queried for the *EntityManagerInterface*. Both of these make it clear that we're working with a concrete instance of *EntityManager* and not a proxy generated by the *ManagerRegistry*.

```php
$entityManagerId = sprintf('doctrine.orm.%s_entity_manager', $entityManager['name']);

$container
    ->setDefinition($entityManagerId, new ChildDefinition('doctrine.orm.entity_manager.abstract'))
    ->setPublic(true)
    ->setArguments([
        new Reference(sprintf('doctrine.dbal.%s_connection', $entityManager['connection'])),
        new Reference(sprintf('doctrine.orm.%s_configuration', $entityManager['name'])),
    ])
    ->setConfigurator([new Reference($managerConfiguratorName), 'configure']);
```

```shell
$ bin/console debug:container EntityManagerInterface

Information for Service "doctrine.orm.default_entity_manager"
=============================================================

 The EntityManager is the central access point to ORM functionality.

 ---------------- ----------------------------------------------------------
  Option           Value
 ---------------- ----------------------------------------------------------
  Service ID       doctrine.orm.default_entity_manager
  Class            Doctrine\ORM\EntityManager
  Tags             container.preload (class: Doctrine\ORM\Proxy\Autoloader)
  Public           yes
  Synthetic        no
  Lazy             yes
  Shared           yes
  Abstract         no
  Autowired        no
  Autoconfigured   no
 ---------------- ----------------------------------------------------------
```

### Closing thoughts

Solving this issue took me hours, but after all, taught me something interesting. My takeaway from this story is to never use the *EntityManagerInterface* as a direct dependency. While it's a pretty straightforward advice to follow when it comes to repositories, you might still be in a habit of injecting either the *EntityManager* or *EntityManagerInterface* directly into other services. If you are, sooner or later you'll find yourself in a similar, unclear situation.

In the end, I went as far as to write a PHPStan extension that forbids it altogether. There are no legitimate cases that I can think of that would require it, and the bugs caused by such simple mistake are really hard to figure out. I can't tell you what to do, but I'm adding this thing to my list of "must-do" things while working on Symfony codebases.

---

*I want to thank those few redditors that took the time to point out that the original version of this article was pretty hard to follow and didn't do enough to explain the actual issue and the solution. You were right!*
