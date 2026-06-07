<?php

namespace App\Enums;

enum VariantGridBackgroundPreset: string
{
    case Darkest = 'darkest';
    case Dark = 'dark';
    case Medium = 'medium';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $preset): string => $preset->value,
            self::cases(),
        );
    }
}
