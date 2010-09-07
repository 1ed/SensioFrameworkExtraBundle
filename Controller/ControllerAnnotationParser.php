<?php

namespace Bundle\Sensio\FrameworkExtraBundle\Controller;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Bundle\Sensio\FrameworkExtraBundle\Configuration\ConfigurationInterface;

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
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class ControllerAnnotationParser
{
    protected $classes;

    public function __construct(AnnotationReader $reader)
    {
        $this->reader = $reader;

        $this->classes = array(
            'Bundle\\Sensio\\FrameworkExtraBundle\\Configuration\\Cache',
            'Bundle\\Sensio\\FrameworkExtraBundle\\Configuration\\ParamConverter',
            'Bundle\\Sensio\\FrameworkExtraBundle\\Configuration\\Template',
        );

        foreach ($this->classes as $class) {
            if (!class_exists($class)) {
                throw new \RuntimeException(sprintf('Unable to autoload "%s" class.', $class));
            }
        }
    }

    /**
     * Registers a core.controller listener.
     *
     * @param Symfony\Component\EventDispatcher\EventDispatcher $dispatcher An EventDispatcher instance
     */
    public function register(EventDispatcher $dispatcher)
    {
        $dispatcher->connect('core.controller', array($this, 'filter'));
    }

    /**
     * 
     *
     * @param Event $event An Event instance
     */
    public function filter(Event $event, $controller)
    {
        if (!is_array($controller)) {
            return $controller;
        }

        $r = new \ReflectionObject($controller[0]);
        $m = $r->getMethod($controller[1]);

        $request = $event->getParameter('request');

        $classes = $this->classes;
        $this->reader->setAnnotationCreationFunction(function ($name, $values) use ($classes)
        {
            if (!in_array($name, $classes)) {
                return null;
            }

            $configuration = new $name();
            foreach ($values as $key => $value) {
                if (!method_exists($configuration, $method = 'set'.$key)) {
                    throw new \BadMethodCallException(sprintf("Unknown annotation attribute '%s' for '%s'.", ucfirst($key), get_class($this)));
                }
                $configuration->$method($value);
            }

            return $configuration;
        });

        $this->reader->setDefaultAnnotationNamespace('Bundle\\Sensio\\FrameworkExtraBundle\\Configuration\\');
        foreach ($this->reader->getMethodAnnotations($m) as $configuration) {
            if ($configuration instanceof ConfigurationInterface) {
                $request->attributes->set('_'.$configuration->getAliasName(), $configuration);
            }
        }

        $this->reader->setAnnotationCreationFunction(function ($name, $values) { return null; });

        return $controller;
    }
}
