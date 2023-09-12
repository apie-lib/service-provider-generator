<?php
namespace Apie\ServiceProviderGenerator\Events;

use Illuminate\Events\Dispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SymfonyEventSubscriberAdapter
{
    public function __construct(
        private readonly EventSubscriberInterface $internal
    ) {
    }

    public function subscribe(Dispatcher $events): array
    {
        return $this->internal->getSubscribedEvents();
    }
}