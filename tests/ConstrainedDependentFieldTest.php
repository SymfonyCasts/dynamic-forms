<?php

/*
 * This file is part of the SymfonyCasts DynamicForms package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\DynamicForms\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;
use Symfonycasts\DynamicForms\Tests\fixtures\DynamicFormsTestKernel;
use Symfonycasts\DynamicForms\Tests\fixtures\Enum\DynamicTestMeal;
use Symfonycasts\DynamicForms\Tests\fixtures\TestConstrainedDynamicForm;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * A dependent field is emptied by the UI whenever one of its dependencies
 * changes. Its validation errors (e.g. NotBlank) must not be rendered in
 * that case, in the same way transformation failures are already hidden.
 *
 * @see https://github.com/SymfonyCasts/dynamic-forms/issues/50
 */
class ConstrainedDependentFieldTest extends KernelTestCase
{
    use HasBrowser;

    public function testNoErrorIsRenderedWhenReturningToThePreviousOption(): void
    {
        $browser = $this->browser();
        // pick Dinner, then Pizza
        $browser->visit('/constrained-form')
            ->selectFieldOption('Meal', 'Dinner')
            ->click('Submit Form')
            ->selectFieldOption('Main food', 'Pizza')
            ->click('Submit Form')
            ->assertSee('Is Form Valid: yes')
        ;

        // change the meal: the submitted "pizza" is not a valid choice anymore,
        // the transformation failure is hidden but the form is invalid
        $browser->selectFieldOption('Meal', 'Breakfast')
            ->click('Submit Form')
            ->assertSee('Is Form Valid: no')
            ->assertNotSee('This food is not served at this meal.')
        ;

        // change the meal back: "mainFood" is submitted empty, its NotBlank
        // violation must be hidden too, but the form must remain invalid
        $browser->selectFieldOption('Meal', 'Dinner')
            ->click('Submit Form')
            ->assertSee('Is Form Valid: no')
            ->assertNotSee('This value should not be blank.')
        ;

        // selecting a food again makes the form valid
        $browser->selectFieldOption('Main food', 'Pasta')
            ->click('Submit Form')
            ->assertSee('Is Form Valid: yes')
        ;
    }

    public function testErrorIsRenderedWhenTheDependencyDidNotChange(): void
    {
        $browser = $this->browser();
        // "Breakfast" is the initial data: submitting it unchanged with an
        // empty mainFood is a real mistake, the NotBlank violation must render
        $browser->visit('/constrained-form')
            ->click('Submit Form')
            ->assertSee('Is Form Valid: no')
            ->assertSee('This value should not be blank.')
        ;
    }

    public function testErrorIsRenderedOnSubmitAfterDependencyChangedInPreviousRender(): void
    {
        $browser = $this->browser()
            ->visit('/constrained-form')
            ->selectFieldOption('Meal', 'Dinner')
            ->click('Submit Form') // re-render after the dependency changed: fine to hide
            ->assertNotSee('This value should not be blank.')
        ;

        // Meal is still "Dinner", the user submits without picking a food
        $browser->click('Submit Form')
            ->assertSee('Is Form Valid: no')
            ->assertSee('This value should not be blank.')
        ;
    }

    public function testErrorIsRenderedWhenTheRenderedStateIsMissing(): void
    {
        $form = self::getContainer()->get('form.factory')->create(TestConstrainedDynamicForm::class, [
            'meal' => DynamicTestMeal::Breakfast,
        ]);

        // "meal" changed, but without the rendered state there is no way to
        // know it: the error must not be hidden
        $form->submit(['meal' => 'dinner', 'mainFood' => '']);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('mainFood')->getErrors());
    }

    public function testErrorIsRenderedWhenADisabledDependencyIsNotSubmitted(): void
    {
        $depOptions = ['choices' => ['A' => 'a', 'B' => 'b'], 'disabled' => true];
        $renderedState = $this->renderDynamicState('b', $depOptions);

        // browsers do not submit disabled fields, Symfony keeps "b"
        $form = $this->createFormWithDependency('b', $depOptions);
        $form->submit(['notes' => '', '__dynamic_state' => $renderedState]);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('notes')->getErrors());
    }

    public function testErrorIsRenderedWhenAnEmptyMultipleDependencyIsNotSubmitted(): void
    {
        $depOptions = ['choices' => ['A' => 'a', 'B' => 'b'], 'multiple' => true];
        $renderedState = $this->renderDynamicState([], $depOptions);

        // browsers omit a multiple choice field with nothing selected
        $form = $this->createFormWithDependency([], $depOptions);
        $form->submit(['notes' => '', '__dynamic_state' => $renderedState]);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('notes')->getErrors());
    }

    private function renderDynamicState(mixed $depData, array $depOptions): string
    {
        return $this->createFormWithDependency($depData, $depOptions)->createView()['__dynamic_state']->vars['value'];
    }

    private function createFormWithDependency(mixed $depData, array $depOptions): FormInterface
    {
        $builder = new DynamicFormBuilder(
            self::getContainer()->get('form.factory')->createBuilder(FormType::class, ['dep' => $depData])
        );
        $builder->add('dep', ChoiceType::class, $depOptions);
        $builder->addDependent('notes', ['dep'], static function (DependentField $field) {
            $field->add(TextType::class, ['constraints' => [new NotBlank()]]);
        });

        return $builder->getForm();
    }

    protected static function getKernelClass(): string
    {
        return DynamicFormsTestKernel::class;
    }
}
