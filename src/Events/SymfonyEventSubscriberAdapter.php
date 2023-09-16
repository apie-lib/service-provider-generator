<?php
namespace Apie\ServiceProviderGenerator\Events;

use Illuminate\Events\Dispatcher;
use Psr\Container\ContainerInterface;

class SymfonyEventSubscriberAdapter
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $serviceId
    ) {
    }

    public function subscribe(Dispatcher $events): array
    {
        $result = [];
        $service = $this->container->get($this->serviceId);
        foreach ($service->getSubscribedEvents() as $event => $eventClosures) {
            $result[$event] = new SymfonyEventListenerAdapter($service, $eventClosures);
        }
        return $result;
    }
}