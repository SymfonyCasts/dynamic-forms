<?php

namespace Symfonycasts\DynamicForms;

use Symfony\Component\Form\FormEvents;

/**
 * Holds the configuration for a dynamic field & what listeners have been executed.
 */
class DependentFieldConfig
{
    public array $callbackExecuted = [
        FormEvents::PRE_SET_DATA => false,
        FormEvents::POST_SUBMIT => false,
    ];

    public function __construct(
        public string $name,
        public array $dependencies,
        public \Closure $callback,
    )
    {
    }

    public function isReady(array $availableDependencyData, string $eventName): bool
    {
        if ($this->callbackExecuted[$eventName]) {
            return false;
        }

        foreach ($this->dependencies as $dependency) {
            if (!array_key_exists($dependency, $availableDependencyData)) {
                return false;
            }
        }

        return true;
    }

    public function execute(array $availableDependencyData, string $eventName): DependentField
    {
        $configurableFormBuilder = new DependentField();

        $this->callbackExecuted[$eventName] = true;
        $dependencyData = array_map(fn (string $dependency) => $availableDependencyData[$dependency], $this->dependencies);
        $this->callback->__invoke($configurableFormBuilder, ...$dependencyData);

        return $configurableFormBuilder;
    }
}
