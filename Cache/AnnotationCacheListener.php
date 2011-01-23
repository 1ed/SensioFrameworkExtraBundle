<?php

namespace Bundle\Sensio\FrameworkExtraBundle\Cache;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * .
 *
 * The filter method must be connected to the core.response event.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class AnnotationCacheListener
{
    /**
     * 
     *
     * @param Event $event An Event instance
     */
    public function filter(Event $event, Response $response)
    {
        if (!$configuration = $event->get('request')->attributes->get('_cache')) {
            return $response;
        }

        if (!$response->isSuccessful()) {
            return $response;
        }

        if (null !== $configuration->getSMaxAge()) {
            $response->setSharedMaxAge($configuration->getSMaxAge());
        }

        if (null !== $configuration->getMaxAge()) {
            $response->setMaxAge($configuration->getMaxAge());
        }

        if (null !== $configuration->getExpires()) {
            $date = \DateTime::create(\DateTime::createFromFormat('U', $configuration->getExpires(), new \DateTimeZone('UTC')));

            $response->setLastModified($date);
        }

        return $response;
    }
}
