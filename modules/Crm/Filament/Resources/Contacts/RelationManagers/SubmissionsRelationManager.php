<?php

/**
 * Khaneh Romantic — A contact's history: every form they sent
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Filament\Resources\Contacts\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Crm\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Modules\Crm\Models\FormSubmission;
use Modules\Crm\Support\Inbox;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'سابقه';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('تاریخ')->jalali(),
                TextColumn::make('form')->label('فرم')->badge()->color('gray')->formatStateUsing(fn ($state) => Inbox::formLabel($state)),
                TextColumn::make('summary')->label('خلاصه')->wrap()->state(fn (FormSubmission $s) => Inbox::summary($s, 200)),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->labels(FormSubmission::STATUSES, FormSubmission::COLORS),
            ])
            ->recordAction('open')
            ->recordActions([FormSubmissionResource::openAction()]);
    }
}
