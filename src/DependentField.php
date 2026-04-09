<?php

/*
 * This file is part of the SymfonyCasts DynamicForms package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\DynamicForms;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Used to configure a dependent/dynamic field.
 *
 * If ->add() is not called, the field won't be included.
 */
class DependentField
{
    private ?string $type = null;
    private array $options = [];
    private bool $shouldBeAdded = false;

    /**
     * @var DataTransformerInterface[]
     */
    private array $modelTransformers;

    /**
     * @var DataTransformerInterface[]
     */
    private array $viewTransformers;

    public function add(?string $type = null, array $options = []): static
    {
        $this->type = $type;
        $this->options = $options;
        $this->shouldBeAdded = true;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function shouldBeAdded(): bool
    {
        return $this->shouldBeAdded;
    }

    public function addModelTransformer(DataTransformerInterface $transformer): self
    {
        $this->modelTransformers[] = $transformer;

        return $this;
    }

    /**
     * @return DataTransformerInterface[]
     */
    public function getModelTransformers(): array
    {
        return $this->modelTransformers;
    }

    public function addViewTransformer(DataTransformerInterface $transformer): self
    {
        $this->viewTransformers[] = $transformer;

        return $this;
    }

    /**
     * @return DataTransformerInterface[]
     */
    public function getViewTransformers(): array
    {
        return $this->viewTransformers;
    }
}
