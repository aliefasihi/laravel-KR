<?php

/**
 * Khaneh Romantic — Admin: modules (switch features on/off, like WordPress plugins)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Filament\Pages;

use App\Core\Modules\ModuleRegistry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use UnitEnum;

class ModulesPage extends Page
{
    protected string $view = 'filament.pages.modules';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|UnitEnum|null $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'ماژول‌ها';

    protected static ?string $title = 'ماژول‌ها';

    protected static ?string $slug = 'modules';

    public string $filter = 'all';

    /** @return list<array<string, mixed>> */
    public function getModules(): array
    {
        $registry = app(ModuleRegistry::class);

        return collect($registry->all())
            ->map(fn (array $m) => $m + [
                'enabled' => $registry->enabled($m['name']),
                'dependents' => $registry->dependents($m['name']),
                'requiresTitles' => array_map($registry->title(...), $m['requires']),
            ])
            ->filter(fn (array $m) => match ($this->filter) {
                'active' => $m['enabled'],
                'inactive' => ! $m['enabled'],
                'optional' => ! $m['core'],
                default => true,
            })
            ->sortBy(fn (array $m) => [$m['core'] ? 1 : 0, $m['title']])
            ->values()
            ->all();
    }

    public function toggleAction(): Action
    {
        return Action::make('toggle')
            ->label(fn (array $arguments) => app(ModuleRegistry::class)->enabled($arguments['module']) ? 'غیرفعال کردن' : 'فعال کردن')
            ->color(fn (array $arguments) => app(ModuleRegistry::class)->enabled($arguments['module']) ? 'gray' : 'primary')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => app(ModuleRegistry::class)->title($arguments['module']))
            ->modalDescription(fn (array $arguments) => app(ModuleRegistry::class)->enabled($arguments['module'])
                ? 'بخش‌های مربوط به این ماژول از سایت و پنل برداشته می‌شوند. داده‌ها پاک نمی‌شوند و با فعال کردن دوباره برمی‌گردند.'
                : 'جدول‌های لازم ساخته می‌شوند و بخش‌های این ماژول به سایت و پنل اضافه می‌شوند.')
            ->action(function (array $arguments) {
                $registry = app(ModuleRegistry::class);
                $name = $arguments['module'];

                try {
                    $registry->enabled($name) ? $registry->disable($name) : $registry->enable($name);
                } catch (RuntimeException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()
                    ->title($registry->enabled($name) ? "«{$registry->title($name)}» فعال شد" : "«{$registry->title($name)}» غیرفعال شد")
                    ->success()->send();

                // menus and routes change with the module set → full reload
                $this->redirect(static::getUrl(), navigate: false);
            });
    }
}
