<?php

/*
 * This file is part of the SymfonyCasts DynamicForms package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\DynamicForms\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvents;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DependentFieldConfig;

class DependentFieldConfigTest extends TestCase
{
    public function testIsReadyReturnsCorrectlyBasedOnDependencies(): void
    {
        $fieldConfig = new DependentFieldConfig('state', ['country'], fn () => null);
        $this->assertFalse($fieldConfig->isReady([], FormEvents::PRE_SET_DATA));
        $this->assertTrue($fieldConfig->isReady(['country' => 'United States'], FormEvents::PRE_SET_DATA));
        $this->assertTrue($fieldConfig->isReady(['country' => 'United States'], FormEvents::POST_SUBMIT));
        $this->assertTrue($fieldConfig->isReady(['country' => 'United States', 'extra' => 'field'], FormEvents::POST_SUBMIT));
    }

    public function testIsReadyReturnFalseIfCallbackExecuted(): void
    {
        $fieldConfig = new DependentFieldConfig('state', ['country'], fn () => null);
        $this->assertTrue($fieldConfig->isReady(['country' => 'United States'], FormEvents::PRE_SET_DATA));
        $fieldConfig->execute(['country' => 'United States'], FormEvents::PRE_SET_DATA);
        $this->assertFalse($fieldConfig->isReady(['country' => 'United States'], FormEvents::PRE_SET_DATA));
        $this->assertTrue($fieldConfig->isReady(['country' => 'United States'], FormEvents::POST_SUBMIT));
    }

    public function testExecuteCallsCallback(): void
    {
        $argsPassedToCallback = null;
        $fieldConfig = new DependentFieldConfig('state', ['country', 'shouldHideRandomStates'], function ($configurableFormBuilder, $country, $shouldHideRandomStates) use (&$argsPassedToCallback) {
            $argsPassedToCallback = [$configurableFormBuilder, $country, $shouldHideRandomStates];
        });
        $fieldConfig->execute(['country' => 'United States', 'shouldHideRandomStates' => true], FormEvents::PRE_SET_DATA);
        $this->assertNotNull($argsPassedToCallback);
        $this->assertInstanceOf(DependentField::class, $argsPassedToCallback[0]);
        $this->assertSame('United States', $argsPassedToCallback[1]);
        $this->assertTrue($argsPassedToCallback[2]);
    }
}
