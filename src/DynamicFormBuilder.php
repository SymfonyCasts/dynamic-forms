<?php

namespace Symfonycasts\DynamicForms;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Wraps the normal form builder & to add addDynamic() to it.
 *
 * @author Ryan Weaver
 */
class DynamicFormBuilder implements FormBuilderInterface, \IteratorAggregate
{
    /**
     * @var DependentFieldConfig[]
     */
    private array $dependentFieldConfigs = [];

    /**
     * The actual form that this builder is turned into.
     */
    private FormInterface $form;

    private array $preSetDataDependencyData = [];
    private array $postSubmitDependencyData = [];

    public function __construct(private FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->form = $event->getForm();
            $this->preSetDataDependencyData = [];
            $this->initializeListeners();
        }, 100);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function () {
            $this->postSubmitDependencyData = [];
        });
    }

    public function addDependent(string $name, array $dependencies, callable $callback): self
    {
        $this->dependentFieldConfigs[] = new DependentFieldConfig($name, $dependencies, $callback);

        return $this;
    }

    public function storePreSetDataDependencyData(FormEvent $event): void
    {
        $dependency = $event->getForm()->getName();
        $this->preSetDataDependencyData[$dependency] = $event->getData();

        $this->executeReadyCallbacks($this->preSetDataDependencyData, FormEvents::PRE_SET_DATA);
    }

    public function storePostSubmitDependencyData(FormEvent $event): void
    {
        $dependency = $event->getForm()->getName();
        $this->postSubmitDependencyData[$dependency] = $event->getForm()->getData();

        $this->executeReadyCallbacks($this->postSubmitDependencyData, FormEvents::POST_SUBMIT);
    }

    private function executeReadyCallbacks(array $availableDependencyData, string $eventName): void
    {
        foreach ($this->dependentFieldConfigs as $dependentFieldConfig) {
            if ($dependentFieldConfig->isReady($availableDependencyData, $eventName)) {
                $dynamicField = $dependentFieldConfig->execute($availableDependencyData, $eventName);
                $name = $dependentFieldConfig->name;

                if (!$dynamicField->shouldBeAdded()) {
                    $this->form->remove($name);

                    continue;
                }

                $this->builder->add($name, $dynamicField->getType(), $dynamicField->getOptions());

                $this->initializeListeners([$name]);
                // auto initialize mimics FormBuilder::getForm() behavior
                $field = $this->builder->get($name)->setAutoInitialize(false)->getForm();
                $this->form->add($field);
            }
        }
    }

    private function initializeListeners(?array $fieldsToConsider = null): void
    {
        $registeredFields = [];
        foreach ($this->dependentFieldConfigs as $dynamicField) {
            foreach ($dynamicField->dependencies as $dependency) {
                if ($fieldsToConsider && !\in_array($dependency, $fieldsToConsider)) {
                    continue;
                }

                // skip dependencies that are possibly not *yet* part of the form
                if (!$this->builder->has($dependency)) {
                    continue;
                }

                if (\in_array($dependency, $registeredFields)) {
                    continue;
                }

                $registeredFields[] = $dependency;

                $this->builder->get($dependency)->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'storePreSetDataDependencyData']);
                $this->builder->get($dependency)->addEventListener(FormEvents::POST_SUBMIT, [$this, 'storePostSubmitDependencyData']);
            }
        }
    }

    /*
     * Pure method declarations below
     */

    public function count(): int
    {
        return $this->builder->count();
    }

    public function add(string|FormBuilderInterface $child, string $type = null, array $options = []): static
    {
        $this->builder->add($child, $type, $options);
        return $this;
    }

    public function create(string $name, string $type = null, array $options = []): FormBuilderInterface
    {
        return $this->builder->create($name, $type, $options);
    }

    public function get(string $name): FormBuilderInterface
    {
        return $this->builder->get($name);
    }

    public function remove(string $name): static
    {
        $this->builder->remove($name);
        return $this;
    }

    public function has(string $name): bool
    {
        return $this->builder->has($name);
    }

    public function all(): array
    {
        return $this->builder->all();
    }

    public function getForm(): FormInterface
    {
        return $this->builder->getForm();
    }

    public function addEventListener(string $eventName, callable $listener, int $priority = 0): static
    {
        $this->builder->addEventListener($eventName, $listener, $priority);
        return $this;
    }

    public function addEventSubscriber(EventSubscriberInterface $subscriber): static
    {
        $this->builder->addEventSubscriber($subscriber);
        return $this;
    }

    public function addViewTransformer(DataTransformerInterface $viewTransformer, bool $forcePrepend = false): static
    {
        $this->builder->addViewTransformer($viewTransformer, $forcePrepend);
        return $this;
    }

    public function resetViewTransformers(): static
    {
        $this->builder->resetViewTransformers();
        return $this;
    }

    public function addModelTransformer(DataTransformerInterface $modelTransformer, bool $forceAppend = false): static
    {
        $this->builder->addModelTransformer($modelTransformer, $forceAppend);
        return $this;
    }

    public function resetModelTransformers(): static
    {
        $this->builder->resetModelTransformers();
        return $this;
    }

    public function setAttribute(string $name, mixed $value): static
    {
        $this->builder->setAttribute($name, $value);
        return $this;
    }

    public function setAttributes(array $attributes): static
    {
        $this->builder->setAttributes($attributes);
        return $this;
    }

    public function setDataMapper(?DataMapperInterface $dataMapper): static
    {
        $this->builder->setDataMapper($dataMapper);
        return $this;
    }

    public function setDisabled(bool $disabled): static
    {
        $this->builder->setDisabled($disabled);
        return $this;
    }

    public function setEmptyData(mixed $emptyData): static
    {
        $this->builder->setEmptyData($emptyData);
        return $this;
    }

    public function setErrorBubbling(bool $errorBubbling): static
    {
        $this->builder->setErrorBubbling($errorBubbling);
        return $this;
    }

    public function setErrorMapping(array $errorMapping): static
    {
        $this->builder->setErrorMapping($errorMapping);
        return $this;
    }

    public function setHelp(?string $help): static
    {
        $this->builder->setHelp($help);
        return $this;
    }

    public function setHelpAttr(array $helpAttr): static
    {
        $this->builder->setHelpAttr($helpAttr);
        return $this;
    }

    public function setHelpHtml(bool $helpHtml): static
    {
        $this->builder->setHelpHtml($helpHtml);
        return $this;
    }

    public function setInheritData(bool $inheritData): static
    {
        $this->builder->setInheritData($inheritData);
        return $this;
    }

    public function setInvalidMessage(?string $invalidMessage): static
    {
        $this->builder->setInvalidMessage($invalidMessage);
        return $this;
    }

    public function setInvalidMessageParameters(array $invalidMessageParameters): static
    {
        $this->builder->setInvalidMessageParameters($invalidMessageParameters);
        return $this;
    }

    public function setLabel(?string $label): static
    {
        $this->builder->setLabel($label);
        return $this;
    }

    public function setLabelAttr(array $labelAttr): static
    {
        $this->builder->setLabelAttr($labelAttr);
        return $this;
    }

    public function setLabelFormat(?string $labelFormat): static
    {
        $this->builder->setLabelFormat($labelFormat);
        return $this;
    }

    public function setLabelHtml(bool $labelHtml): static
    {
        $this->builder->setLabelHtml($labelHtml);
        return $this;
    }

    public function setMapped(bool $mapped): static
    {
        $this->builder->setMapped($mapped);
        return $this;
    }

    public function setMethod(string $method): static
    {
        $this->builder->setMethod($method);
        return $this;
    }

    public function setOptions(array $options): static
    {
        $this->builder->setOptions($options);
        return $this;
    }

    public function setPropertyPath(null|string|PropertyPathInterface $propertyPath): static
    {
        $this->builder->setPropertyPath($propertyPath);
        return $this;
    }

    public function setRequired(bool $required): static
    {
        $this->builder->setRequired($required);
        return $this;
    }

    public function setRowAttr(array $rowAttr): static
    {
        $this->builder->setRowAttr($rowAttr);
        return $this;
    }

    public function setTranslationDomain(?string $translationDomain): static
    {
        $this->builder->setTranslationDomain($translationDomain);
        return $this;
    }

    public function setTrim(bool $trim): static
    {
        $this->builder->setTrim($trim);
        return $this;
    }

    public function setAction(?string $action): static
    {
        $this->builder->setAction($action);
        return $this;
    }

    public function setCompound(bool $compound): static
    {
        $this->builder->setCompound($compound);
        return $this;
    }

    public function setDataClass(?string $dataClass): static
    {
        $this->builder->setDataClass($dataClass);
        return $this;
    }

    public function setDataLocked(bool $locked): static
    {
        $this->builder->setDataLocked($locked);
        return $this;
    }

    public function setFormFactory(FormFactoryInterface $formFactory): static
    {
        $this->builder->setFormFactory($formFactory);
        return $this;
    }

    public function setType(?ResolvedFormTypeInterface $type): static
    {
        $this->builder->setType($type);
        return $this;
    }

    public function setTypeName(string $typeName): static
    {
        $this->builder->setTypeName($typeName);
        return $this;
    }

    public function setEventDispatcher(EventDispatcherInterface $dispatcher): static
    {
        $this->builder->setEventDispatcher($dispatcher);
        return $this;
    }

    public function setRequestHandler(?RequestHandlerInterface $requestHandler): static
    {
        $this->builder->setRequestHandler($requestHandler);
        return $this;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->builder->getAttribute($name, $default);
    }

    public function hasAttribute(string $name): bool
    {
        return $this->builder->hasAttribute($name);
    }

    public function getAttributes(): array
    {
        return $this->builder->getAttributes();
    }

    public function getDataMapper(): ?DataMapperInterface
    {
        return $this->builder->getDataMapper();
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->builder->getEventDispatcher();
    }

    public function getName(): string
    {
        return $this->builder->getName();
    }

    public function getPropertyPath(): ?PropertyPathInterface
    {
        return $this->builder->getPropertyPath();
    }

    public function getRequestHandler(): RequestHandlerInterface
    {
        return $this->builder->getRequestHandler();
    }

    public function getType(): ResolvedFormTypeInterface
    {
        return $this->builder->getType();
    }

    public function setByReference(bool $byReference): static
    {
        $this->builder->setByReference($byReference);

        return $this;
    }

    public function setData(mixed $data): static
    {
        $this->builder->setData($data);

        return $this;
    }

    public function setAutoInitialize(bool $initialize): static
    {
        $this->builder->setAutoInitialize($initialize);

        return $this;
    }

    public function getFormConfig(): FormConfigInterface
    {
        return $this->builder->getFormConfig();
    }

    public function setIsEmptyCallback(?callable $isEmptyCallback): static
    {
        $this->builder->setIsEmptyCallback($isEmptyCallback);

        return $this;
    }

    public function getMapped(): bool
    {
        return $this->builder->getMapped();
    }

    public function getByReference(): bool
    {
        return $this->builder->getByReference();
    }

    public function getInheritData(): bool
    {
        return $this->builder->getInheritData();
    }

    public function getCompound(): bool
    {
        return $this->builder->getCompound();
    }

    public function getViewTransformers(): array
    {
        return $this->builder->getViewTransformers();
    }

    public function getModelTransformers(): array
    {
        return $this->builder->getModelTransformers();
    }

    public function getRequired(): bool
    {
        return $this->builder->getRequired();
    }

    public function getDisabled(): bool
    {
        return $this->builder->getDisabled();
    }

    public function getErrorBubbling(): bool
    {
        return $this->builder->getErrorBubbling();
    }

    public function getEmptyData(): mixed
    {
        return $this->builder->getEmptyData();
    }

    public function getData(): mixed
    {
        return $this->builder->getData();
    }

    public function getDataClass(): ?string
    {
        return $this->builder->getDataClass();
    }

    public function getDataLocked(): bool
    {
        return $this->builder->getDataLocked();
    }

    public function getFormFactory(): FormFactoryInterface
    {
        return $this->builder->getFormFactory();
    }

    public function getAction(): string
    {
        return $this->builder->getAction();
    }

    public function getMethod(): string
    {
        return $this->builder->getMethod();
    }

    public function getAutoInitialize(): bool
    {
        return $this->builder->getAutoInitialize();
    }

    public function getOptions(): array
    {
        return $this->builder->getOptions();
    }

    public function hasOption(string $name): bool
    {
        return $this->builder->hasOption($name);
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->builder->getOption($name, $default);
    }

    public function getIsEmptyCallback(): ?callable
    {
        return $this->builder->getIsEmptyCallback();
    }

    public function getIterator(): \Traversable
    {
        return $this->builder;
    }
}

