[//]: # (TITLE: Using Doctrine's EntityRepository as a service is a bad idea)
[//]: # (DESCRIPTION: Why Doctrine's ServiceEntityRepository, and not EntityRepository, should be only be ever used as a service)
[//]: # (DATE: 2023-01-20)
[//]: # (TAGS: symfony, entity manager, service repository)

If you're using Symfony, you've definitely written a Doctrine repository at some point. If you've been using Symfony long enough, you've probably seen the introduction of *ServiceEntityRepository* that complemented the default *EntityRepository* base class. Its initial goal was to ease the use of repositories with service containers, instead of being tied directly to Doctrine, but that's not what I want to talk about. I really want to talk about the implications of using the *EntityRepository* in a project, where every other repository extends the *ServiceEntityRepository*.

During a bug-fixing-technical-debt-reducing period in one of the projects I'm working on, I decided to take a look at a long-standing issue that was popping up in logs from time to time. It wasn't anything critical as the crash was happening in the context of Symfony's message consumer in the Messenger component, but the handler run often enough to eventually do what it was supposed to do. The exception we've been getting was "Entity manager is closed" thrown by Doctrine. It wasn't apparent why it was thrown in the first place, because none of the operations executed by Doctrine, before this exception, failed in any way.

I've spent hours verifying the database schema and various input sets while trying to reproduce the crash locally. Eventually, I decided to look at it in a broader context - the handler was executed by a long-running consumer. It became apparent that the reason for throwing that exception might've originated from a different part of the code that wasn't part of the executions paths under the affected handler. I looked at logs preceding the crash and noticed a pattern where it would only throw an exception if a previously consumed message resulted in the Doctrine exception as well.

I was on the right track. At this point, I knew that the entity manager was being closed because of an exception thrown by a different handler processing a previous message. One question remained - why wasn't Doctrine reset to its original state before consuming a new message? After all, Messenger handles it implicitly through the [*DoctrinePingConnectionMiddleware*](https://github.com/symfony/doctrine-bridge/blob/5.4/Messenger/DoctrinePingConnectionMiddleware.php) - it resets the manager before passing the execution to the rest of the middleware stack.

Here's where the topic of this article comes to light. It's because of the difference between the constructors of *EntityRepository* and *ServiceEntityRepository*. Let's take a look at both of these:

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

We can immediately notice two things - first, the *ServiceEntityRepository* is extending the *EntityRepository*; second, in contrast to its parent, *ServiceEntityRepository* isn't constructed with an object implementing the *EntityManagerInterface*, but with an instance of *ManagerRegistry* instead. What's the difference?

When an *EntityManager* encounters a driver exception during the *flush* operation, it's going to close. You won't be able to use this instance anymore as there's no way to make it reopen. By extension, all instances of repositories using that exact instance of *EntityManager* will become useless.
You can, of course, reach the *ManagerRegistry* and call the *resetManager* method, but that won't fix anything, aside from giving you a fresh instance *EntityManager*. With *ServiceEntityRepository* it's a different story. Symfony implements its own *ManagerRegistry* that knows how to talk to a PSR's *ContainerInterface* and the *EntityManager* returned by the *ManagerRegistry::getManagerForClass* method is a proxy! When such *ManagerRegistry* is asked for a fresh *EntityManager*, it doesn't only construct a new one, it updates that same proxy with a fresh, connected instance. That way, when you reset the manager as part of your exception-handling procedure, this change is propagated throughout the system.

With all that, let's come back to my original issue. At this point, we understand that the *EntityManager* should've been reset before handling a new message, but it wasn't. When I took a look at the affected repository one more time, I noticed the issue. It was extending the *EntityRepository*, not the *ServiceEntityRepository*! When the messenger's middleware reset the manager, that repository wasn't affected by that change! The first *flush* operation had to fail because it was using an obsolete entity manager.

At this point, you might be a little confused. Didn't I just say that *ServiceEntityRepository* constructs itself with a proxy to *EntityManager*? How am I sure it's not the same with Doctrine's *EntityManager*? It's because of its service definition and how it's fetched from the service container. If you take a look at the [*DependencyInjection\\DoctrineExtension*](https://github.com/doctrine/DoctrineBundle/blob/2.8.x/DependencyInjection/DoctrineExtension.php), you'll notice that every configured entity manager is a service.

```php
$container
    ->setDefinition($entityManagerId, new ChildDefinition('doctrine.orm.entity_manager.abstract'))
    ->setPublic(true)
    ->setArguments([
        new Reference(sprintf('doctrine.dbal.%s_connection', $entityManager['connection'])),
        new Reference(sprintf('doctrine.orm.%s_configuration', $entityManager['name'])),
    ])
    ->setConfigurator([new Reference($managerConfiguratorName), 'configure']);
```

Additionally, since the *EntityManagerInterface* is an alias to the *default_entity_manager*, you can be sure that when injected directly, it won't be a proxy.

```
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
  Factory Class    Doctrine\ORM\EntityManager                                
  Factory Method   create                                                    
 ---------------- ----------------------------------------------------------
```

My takeaway from this case is to never use the *EntityManagerInterface* as a direct dependency. I even went as far as writing a PHPStan extension that forbids it. There are no legitimate cases that I can't think of that would require it, and the bugs caused by incorrect usage are really hard to track. I can't tell you what to do, but as for me - I'm adding this thing to my list of "must-do" things while working on Symfony codebases.
