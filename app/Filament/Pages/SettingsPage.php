<?php

/**
 * Khaneh Romantic — Admin: store settings (one tab per enabled module)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Filament\Pages;

use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'تنظیمات فروشگاه';

    protected static ?string $title = 'تنظیمات فروشگاه';

    protected static ?string $slug = 'settings';

    /** @var array<string, array<string, mixed>> */
    public ?array $data = [];

    public function mount(): void
    {
        $data = [];
        foreach (Settings::sections() as $key => $section) {
            foreach ($section->defaults as $field => $default) {
                $data[$key][$field] = $default;
            }
        }
        foreach (app(Settings::class)->all() as $fullKey => $value) {
            [$section, $field] = array_pad(explode('.', $fullKey, 2), 2, null);
            if ($field !== null && isset(Settings::sections()[$section])) {
                $data[$section][$field] = $value;
            }
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        $sections = Settings::sections();

        return $schema
            ->statePath('data')
            ->components($sections === []
                ? [Text::make('هیچ ماژولی تنظیمات ندارد.')]
                : [
                    Tabs::make('settings')
                        ->persistTabInQueryString()
                        ->tabs(array_values(array_map(fn (SettingsSection $section) => Tab::make($section->title)
                            ->id($section->key)
                            ->icon($section->icon)
                            ->statePath($section->key)
                            ->schema(($section->schema)()), $sections))),
                ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([Action::make('save')->label('ذخیره‌ی تنظیمات')->submit('save')->keyBindings(['mod+s'])])]),
        ]);
    }

    public function save(): void
    {
        $values = [];
        foreach ($this->form->getState() as $section => $fields) {
            foreach ((array) $fields as $field => $value) {
                $values[$section.'.'.$field] = $value;
            }
        }

        Settings::set($values);

        Notification::make()->success()->title('تنظیمات ذخیره شد')->send();
    }
}
