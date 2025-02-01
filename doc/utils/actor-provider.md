# Actor Provider

The Doctrine Extensions package includes support for an "actor provider" for extensions which use a user value, such as
the blameable or loggable extensions.

## Index

- [Getting Started](#getting-started)
- [Benefits of Actor Providers](#benefits-of-actor-providers)

## Getting Started

Out of the box, the library does not provide an implementation for the `Gedmo\Tool\ActorProviderInterface`, so you will
need to create a class in your application. Below is an example of an actor provider using Symfony's Security components:

```php
namespace App\Utils;

use Gedmo\Tool\ActorProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class SymfonyActorProvider implements ActorProviderInterface
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return object|string|null
     */
    public function getActor()
    {
        $token = $this->tokenStorage->getToken();

        return $token ? $token->getUser() : null;
    }
}
```

Once you've created your actor provider, you can inject it into the listeners for supported extensions by calling
the `setActorProvider` method.

```php
/** Gedmo\Blameable\BlameableListener $listener */
$listener->setActorProvider($provider);
```

## Benefits of Actor Providers

Unlike the previously existing APIs for the extensions which support user references, actor providers allow lazily
resolving the user value when it is needed instead of eagerly fetching it when the listener is created. Actor providers
would also integrate nicely with long-running processes such as FrankenPHP where the provider can be reset between
requests.
