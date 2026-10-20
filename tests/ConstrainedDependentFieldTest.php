<?php

/*
 * This file is part of the SymfonyCasts DynamicForms package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\DynamicForms\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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

    protected static function getKernelClass(): string
    {
        return DynamicFormsTestKernel::class;
    }
}
