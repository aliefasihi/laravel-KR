<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Comments\Filament\Resources\Comments\Pages;

use App\Core\Support\PersianText;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Modules\Comments\Filament\Resources\Comments\CommentResource;
use Modules\Comments\Models\Comment;

class ListComments extends ListRecords
{
    protected static string $resource = CommentResource::class;

    public function getTabs(): array
    {
        $counts = Comment::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $tabs = [];
        foreach (['pending', 'approved', 'rejected', 'spam'] as $status) {
            $tabs[$status] = Tab::make(Comment::STATUSES[$status])
                ->badge(($n = (int) ($counts[$status] ?? 0)) ? PersianText::toPersianDigits($n) : null)
                ->badgeColor(Comment::COLORS[$status])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status));
        }

        return ['all' => Tab::make('همه')] + $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return Comment::query()->where('status', 'pending')->exists() ? 'pending' : 'all';
    }
}
