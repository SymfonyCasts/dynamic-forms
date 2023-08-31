<?php

/*
 * This file is part of the SymfonyCasts DynamicForms package.
 * Copyright (c) SymfonyCasts <https://symfonycasts.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfonycasts\DynamicForms\Tests\fixtures\Enum;

enum DynamicTestPizzaSize: int
{
    case Small = 12;
    case Medium = 14;
    case Large = 16;

    public function getReadable(): string
    {
        return match ($this) {
            self::Small => '12 inch',
            self::Medium => '14 inch',
            self::Large => '16 inch',
        };
    }
}
