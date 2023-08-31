<?php

/*
 * This file is part of the SymfonyCasts DynamicForms package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\DynamicForms\Tests\fixtures;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;
use Symfonycasts\DynamicForms\Tests\fixtures\Enum\DynamicTestFood;
use Symfonycasts\DynamicForms\Tests\fixtures\Enum\DynamicTestMeal;
use Symfonycasts\DynamicForms\Tests\fixtures\Enum\DynamicTestPizzaSize;

class TestDynamicForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder->add('meal', EnumType::class, [
            'class' => DynamicTestMeal::class,
            'choice_label' => fn (DynamicTestMeal $meal): string => $meal->getReadable(),
            'placeholder' => 'Which meal is it?',
        ]);

        $builder->add('upperCasePizzaSizes', CheckboxType::class, [
            'mapped' => false,
        ]);

        // addDynamic(string $name, array $dependencies, callable $callback): self
        $builder->addDependent('mainFood', ['meal'], function (DependentField $field, ?DynamicTestMeal $meal) {
            $field->add(EnumType::class, [
                'class' => DynamicTestFood::class,
                'placeholder' => null === $meal ? 'Select a meal first' : sprintf('What is for %s?', $meal->getReadable()),
                'choices' => $meal?->getFoodChoices(),
                'choice_label' => fn (DynamicTestFood $food): string => $food->getReadable(),
                'disabled' => null === $meal,
            ]);
        });

        $builder->addDependent('pizzaSize', ['mainFood', 'upperCasePizzaSizes'], function (DependentField $field, ?DynamicTestFood $food, bool $upperCasePizzaSizes) {
            if (DynamicTestFood::Pizza !== $food) {
                return;
            }

            $field->add(EnumType::class, [
                'class' => DynamicTestPizzaSize::class,
                'placeholder' => $upperCasePizzaSizes ? strtoupper('What size pizza?') : 'What size pizza?',
                'choice_label' => fn (DynamicTestPizzaSize $pizzaSize): string => $upperCasePizzaSizes ? strtoupper($pizzaSize->getReadable()) : $pizzaSize->getReadable(),
                'required' => true,
            ]);
        });
    }
}
