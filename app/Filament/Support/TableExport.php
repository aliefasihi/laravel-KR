<?php

/**
 * Khaneh Romantic — «خروجی اکسل» on every admin list: the table as you see it, as an Excel file
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Added to every resource list page and relation manager (customers, orders, forms, newsletter, club,
 * registries, wholesale, returns, questions, reviews…) by AppServiceProvider, so a new list gets it too.
 *
 *  - Rows: everything the current search, filters, tab and sort select — all pages, not only this one.
 *  - Columns: every column of the table (also the ones toggled off), with the same labels and texts the
 *    table shows (statuses, Jalali dates…). Amounts and counts stay numbers so Excel can sum them;
 *    mobiles, codes and anything with a leading zero stay text. Digits are written 0-9 (۰۹۱۲… → 0912…)
 *    so the file works in Excel formulas, CRMs and SMS panels.
 *  - .xlsx (right-to-left sheet, header row frozen) or .csv (UTF-8 for Excel).
 */

namespace App\Filament\Support;

use App\Core\Support\Jalali;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Contracts\HasLabel;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use UnitEnum;

final class TableExport
{
    /** Bigger exports would make the admin wait too long in one request. */
    public const MAX_ROWS = 50_000;

    /** Every list page / relation manager gets the export buttons. */
    public static function register(): void
    {
        Table::configureUsing(function (Table $table): void {
            $livewire = $table->getLivewire();
            if ($livewire instanceof ListRecords || $livewire instanceof RelationManager) {
                $table->pushHeaderActions([self::action()]);
            }
        });
    }

    public static function action(): ActionGroup
    {
        return ActionGroup::make([
            Action::make('exportXlsx')->label('اکسل (xlsx)')->icon('heroicon-o-table-cells')
                ->action(fn (HasTable $livewire) => self::download($livewire, 'xlsx')),
            Action::make('exportCsv')->label('CSV')->icon('heroicon-o-document-text')
                ->action(fn (HasTable $livewire) => self::download($livewire, 'csv')),
        ])
            ->label('خروجی')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->button()
            ->tooltip('همه‌ی ردیف‌های این فهرست با جستجو و فیلترهای فعلی');
    }

    public static function download(HasTable $livewire, string $format = 'xlsx'): BinaryFileResponse|StreamedResponse
    {
        [$headers, $rows] = self::rows($livewire);
        [$y, $m, $d] = array_values(Jalali::parts(now(config('khaneh.display_timezone'))));
        $name = self::filename($livewire).'-'.sprintf('%04d-%02d-%02d', $y, $m, $d);

        if ($format === 'csv' || ! class_exists(Writer::class)) {
            return response()->streamDownload(function () use ($headers, $rows) {
                $out = fopen('php://output', 'wb');
                fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM: Excel shows Persian correctly
                fputcsv($out, $headers, escape: '');
                foreach ($rows as $row) {
                    fputcsv($out, $row, escape: '');
                }
                fclose($out);
            }, $name.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $path = tempnam(sys_get_temp_dir(), 'kr-export-').'.xlsx';
        $options = new Options;
        foreach (self::widths($headers, $rows) as $index => $width) {
            $options->setColumnWidth($width, $index + 1);
        }
        $writer = new Writer($options);
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName(Str::limit(self::title($livewire), 28, ''));
        $writer->getCurrentSheet()->setSheetView((new SheetView)->setRightToLeft(true)->setFreezeRow(2));
        $writer->addRow(Row::fromValues($headers, (new Style)->setFontBold()));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return response()->download($path, $name.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * The table's columns and the rows its current search/filters/sort select.
     *
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    public static function rows(HasTable $livewire): array
    {
        $table = $livewire->getTable();
        /** @var list<Column> $columns */
        // pictures and colour swatches carry nothing useful in a spreadsheet
        $columns = array_values(array_filter($table->getColumns(), fn (Column $column) => ! $column instanceof ImageColumn && ! $column instanceof ColorColumn
            && ! rescue(fn () => $column->isHidden(), false, report: false)));

        // one Excel column per table column, plus one for a text shown under/over it (e.g. the mobile under a name)
        $cells = [];
        $headers = [];
        foreach ($columns as $column) {
            $label = self::text($column->getLabel()) ?: $column->getName();
            $cells[] = [$column, 'state'];
            $headers[] = $label;
            if (method_exists($column, 'hasDescription') && $column->hasDescription()) {
                $cells[] = [$column, 'description'];
                $headers[] = $label.' (جزئیات)';
            }
        }

        $query = $livewire->getFilteredSortedTableQuery();
        $rows = [];
        if ($query === null) {
            return [$headers, $rows];
        }

        $relations = null;
        $query->chunk(500, function ($records) use ($columns, $cells, &$rows, &$relations) {
            // relations the columns read (customer.name…), loaded once per chunk instead of once per row
            $relations ??= self::relations($columns, $records->first());
            if ($relations) {
                rescue(fn () => $records->loadMissing($relations), report: false);
            }
            foreach ($records as $record) {
                $rows[] = array_map(fn (array $cell) => $cell[1] === 'state' ? self::cell($cell[0], $record) : self::description($cell[0], $record), $cells);
            }
            if (count($rows) >= self::MAX_ROWS) {
                return false;
            }
        });

        return [$headers, $rows];
    }

    /** One cell: the text the table shows; plain numbers become real numbers. */
    public static function cell(Column $column, Model $record): string|int|float|null
    {
        try {
            $column->record($record);
            $state = $column->getState();
            $items = is_iterable($state) && ! is_string($state) ? collect($state)->all() : [$state];
            $texts = array_map(fn ($item) => self::text(self::format($column, $item)), $items);
            $text = trim(implode('، ', array_filter($texts, fn ($t) => $t !== '')));
        } catch (Throwable) {
            return null;
        }

        // digits as 0-9 everywhere: mobiles, codes and dates work in Excel, CRMs and SMS panels;
        // codes (SKU, order number, tracking…) stay text even when they are all digits
        $isCode = (bool) preg_match('/(sku|code|number|mobile|phone|tracking|postal|card|iban|sheba|reference|ref)/i', $column->getName());

        return ($isCode ? null : self::number($text)) ?? PersianText::toLatinDigits($text);
    }

    /** the small text a column shows above/below its value */
    public static function description(Column $column, Model $record): string|int|float|null
    {
        try {
            $column->record($record);
            $text = trim(self::text($column->getDescriptionAbove()).' '.self::text($column->getDescriptionBelow()));
        } catch (Throwable) {
            return null;
        }

        return self::number($text) ?? PersianText::toLatinDigits($text);
    }

    private static function format(Column $column, mixed $state): mixed
    {
        if (is_bool($state)) {
            return $state ? 'بله' : 'خیر';
        }
        if (method_exists($column, 'formatState')) {
            $state = $column->formatState($state);
        }

        return match (true) {
            $state instanceof HasLabel => $state->getLabel(),
            $state instanceof BackedEnum => $state->value,
            $state instanceof UnitEnum => $state->name,
            $state instanceof \DateTimeInterface => Jalali::dateTime($state),
            default => $state,
        };
    }

    private static function text(mixed $value): string
    {
        if ($value instanceof Htmlable) {
            $value = html_entity_decode(strip_tags($value->toHtml()), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if (is_array($value)) {
            $value = implode('، ', array_map(fn ($v) => self::text($v), $value));
        }

        return is_scalar($value) ? trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '') : '';
    }

    /** «۱٬۲۰۰٬۰۰۰ تومان» → 1200000, «۱۲» → 12; mobiles/codes with a leading zero stay text. */
    private static function number(string $text): int|float|null
    {
        $plain = trim(str_replace(['٬', ',', ' تومان', 'تومان', '٪', ' '], '', PersianText::toLatinDigits($text)));
        if ($plain === '' || ! preg_match('/^-?\d+(\.\d+)?$/', $plain) || (strlen($plain) > 1 && str_starts_with($plain, '0') && ! str_starts_with($plain, '0.')) || strlen($plain) > 15) {
            return null;
        }

        return str_contains($plain, '.') ? (float) $plain : (int) $plain;
    }

    /** @param  list<Column>  $columns  @return list<string> */
    private static function relations(array $columns, ?Model $record): array
    {
        if ($record === null) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (Column $column) => rescue(fn () => $column->getRelationshipName($record), null, report: false),
            $columns,
        ))));
    }

    /** @return list<float> roughly the longest text of each column, between 10 and 60 characters */
    private static function widths(array $headers, array $rows): array
    {
        $widths = array_map(fn ($h) => mb_strlen((string) $h), $headers);
        foreach (array_slice($rows, 0, 300) as $row) {
            foreach ($row as $i => $value) {
                $widths[$i] = max($widths[$i] ?? 0, mb_strlen((string) $value));
            }
        }

        return array_map(fn ($w) => (float) min(60, max(10, $w * 1.15 + 3)), $widths);
    }

    private static function title(HasTable $livewire): string
    {
        $title = method_exists($livewire, 'getTitle') ? self::text($livewire->getTitle()) : '';
        if ($livewire instanceof RelationManager) {
            $title = self::text($livewire->getTable()->getHeading()) ?: $title;
        }

        return $title !== '' ? $title : 'Export';
    }

    private static function filename(HasTable $livewire): string
    {
        if ($livewire instanceof ListRecords) {
            return Str::slug(class_basename($livewire::getResource()::getModel())).'s';
        }
        if ($livewire instanceof RelationManager) {
            return Str::slug($livewire->getRelationshipName());
        }

        return 'export';
    }
}
