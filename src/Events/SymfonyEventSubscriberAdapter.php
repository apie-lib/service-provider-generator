<?php
namespace Apie\ServiceProviderGenerator\Events;

use Illuminate\Events\Dispatcher;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SymfonyEventSubscriberAdapter
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $serviceId
    ) {
    }

    public function subscribe(Dispatcher $events): array
    {
        return $this->container->get($this->serviceId)->getSubscribedEvents();
    }
}