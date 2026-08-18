<?php

/*
 * This file is part of the SymfonyCasts DynamicForms package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\DynamicForms\Tests\fixtures;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;
use Symfonycasts\DynamicForms\Tests\fixtures\Enum\DynamicTestFood;
use Symfonycasts\DynamicForms\Tests\fixtures\Enum\DynamicTestMeal;

class TestConstrainedDynamicForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder->add('meal', EnumType::class, [
            'class' => DynamicTestMeal::class,
            'choice_label' => static fn (DynamicTestMeal $meal): string => $meal->getReadable(),
            'placeholder' => 'Which meal is it?',
        ]);

        $builder->addDependent('mainFood', ['meal'], static function (DependentField $field, ?DynamicTestMeal $meal) {
            $field->add(EnumType::class, [
                'class' => DynamicTestFood::class,
                'placeholder' => null === $meal ? 'Select a meal first' : \sprintf('What is for %s?', $meal->getReadable()),
                'choices' => $meal?->getFoodChoices(),
                'choice_label' => static fn (DynamicTestFood $food): string => $food->getReadable(),
                'disabled' => null === $meal,
                'constraints' => [new NotBlank()],
            ]);
        });
    }
}
