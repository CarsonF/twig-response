<?php declare(strict_types=1);

namespace Gmo\Web\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An Event Subscriber that doesn't listen to any events.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class NullListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [];
    }
}
