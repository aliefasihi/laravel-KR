<?php

/**
 * Khaneh Romantic — Inbox: one tab per form (new ones counted), export of the open tab
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Filament\Resources\FormSubmissions\Pages;

use App\Core\Support\PersianText;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Modules\Crm\Filament\Resources\Contacts\ContactResource;
use Modules\Crm\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Modules\Crm\Models\FormSubmission;
use Modules\Crm\Support\Csv;
use Modules\Crm\Support\Forms;
use Modules\Crm\Support\Inbox;

class ListFormSubmissions extends ListRecords
{
    protected static string $resource = FormSubmissionResource::class;

    public function getTabs(): array
    {
        $new = FormSubmission::query()->where('status', 'new')->selectRaw('form, count(*) as c')->groupBy('form')->pluck('c', 'form');
        $forms = Forms::all();
        // forms that have records but are no longer defined (module switched off) still get a tab
        foreach (FormSubmission::query()->distinct()->pluck('form') as $key) {
            $forms[$key] ??= null;
        }

        $tabs = ['all' => Tab::make('همه')->badge(($n = (int) $new->sum()) ? PersianText::toPersianDigits($n) : null)->badgeColor('warning')];
        foreach ($forms as $key => $form) {
            $tabs[$key] = Tab::make($form?->label ?? $key)
                ->icon($form?->icon)
                ->badge(($n = (int) ($new[$key] ?? 0)) ? PersianText::toPersianDigits($n) : null)->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('form', $key));
        }

        return $tabs;
    }

    public function updatedActiveTab(): void
    {
        $this->resetTable();   // columns depend on the tab: rebuild the table for the new one
        parent::updatedActiveTab();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label('خروجی CSV این تب')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(function () {
                    $form = $this->activeTab !== 'all' ? $this->activeTab : null;
                    $query = $this->getFilteredSortedTableQuery()->with('contact');
                    [$headers, $rows] = Inbox::export($query->lazyById(500), $form);

                    return Csv::download('inbox-'.($form ?? 'all').'-'.now()->format('Ymd-His').'.csv', $headers, $rows);
                }),
            Action::make('contacts')->label('مخاطبان')->icon('heroicon-o-user-group')->color('gray')->url(ContactResource::getUrl()),
        ];
    }
}
