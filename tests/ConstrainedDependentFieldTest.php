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
            ->assertNotContains('<li')
        ;

        // change the meal back: "mainFood" is submitted empty, its NotBlank
        // violation must be hidden too, but the form must remain invalid
        $browser->selectFieldOption('Meal', 'Dinner')
            ->click('Submit Form')
            ->assertSee('Is Form Valid: no')
            ->assertNotContains('<li')
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

    protected static function getKernelClass(): string
    {
        return DynamicFormsTestKernel::class;
    }
}
