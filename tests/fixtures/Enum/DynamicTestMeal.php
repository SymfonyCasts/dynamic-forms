<?php

namespace Symfonycasts\DynamicForms\Tests\fixtures\Enum;

enum DynamicTestMeal: string
{
    case Breakfast = 'breakfast';
    case SecondBreakfast = 'second_breakfast';
    case Elevenses = 'elevenses';
    case Lunch = 'lunch';
    case Dinner = 'dinner';

    public function getReadable(): string
    {
        return match ($this) {
            self::Breakfast => 'Breakfast',
            self::SecondBreakfast => 'Second Breakfast',
            self::Elevenses => 'Elevenses',
            self::Lunch => 'Lunch',
            self::Dinner => 'Dinner',
        };
    }

    /**
     * @return list<DynamicTestFood>
     */
    public function getFoodChoices(): array
    {
        return match ($this) {
            self::Breakfast => [DynamicTestFood::Eggs, DynamicTestFood::Bacon, DynamicTestFood::Strawberries, DynamicTestFood::Croissant],
            self::SecondBreakfast => [DynamicTestFood::Bagel, DynamicTestFood::Kiwi, DynamicTestFood::Avocado, DynamicTestFood::Waffles],
            self::Elevenses => [DynamicTestFood::Pancakes, DynamicTestFood::Strawberries, DynamicTestFood::Tea],
            self::Lunch => [DynamicTestFood::Sandwich, DynamicTestFood::Cheese, DynamicTestFood::Sushi],
            self::Dinner => [DynamicTestFood::Pizza, DynamicTestFood::Pint, DynamicTestFood::Pasta],
        };
    }
}
