# IP Address Provider

The Doctrine Extensions package includes support for an "IP address provider" for extensions which use an IP address value, such as
the IP traceable extension.

## Index

- [Getting Started](#getting-started)

## Getting Started

Out of the box, the library does not provide an implementation for the `Gedmo\Tool\IpAddressProviderInterface`, so you will
need to create a class in your application. Below is an example of an IP address provider using Symfony's HttpFoundation component:

```php
namespace App\Utils;

use Gedmo\Tool\IpAddressProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestIpAddressProvider implements IpAddressProviderInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getAddress(): ?string
    {
        $request = $this->requestStack->getMainRequest();

        return $request ? $request->getClientIp() : null;
    }
}
```

Once you've created your IP address provider, you can inject it into the listeners for supported extensions by calling
the `setIpAddressProvider` method.

```php
/** @var Gedmo\IpTraceable\IpTraceableListener $listener */
$listener->setIpAddressProvider($provider);
```
