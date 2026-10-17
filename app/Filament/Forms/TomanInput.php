<?php

/**
 * Khaneh Romantic — Price field: staff type Toman, database stores Rials
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Filament\Forms;

use Filament\Forms\Components\TextInput;

final class TomanInput
{
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->minValue(0)
            ->suffix('تومان')
            ->formatStateUsing(fn ($state) => $state === null ? null : intdiv((int) $state, 10))
            ->dehydrateStateUsing(fn ($state) => blank($state) ? null : (int) $state * 10);
    }
}
