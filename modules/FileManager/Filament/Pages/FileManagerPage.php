<?php

/**
 * Khaneh Romantic — Admin: file manager (folders + images)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\FileManager\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Modules\Catalog\Models\Product;
use Modules\FileManager\Models\MediaFolder;
use Modules\FileManager\Support\FolderManager;
use Modules\Media\Models\Media;
use UnitEnum;

class FileManagerPage extends Page
{
    protected string $view = 'filemanager::filament.file-manager';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'فایل منیجر';

    protected static ?string $title = 'فایل منیجر';

    protected static ?string $slug = 'file-manager';

    #[Url(as: 'folder')]
    public ?int $folderId = null;

    #[Url(as: 'q')]
    public string $search = '';

    public int $limit = 60;

    public function mount(): void
    {
        app(FolderManager::class)->productsRoot(); // «محصولات» is always there
    }

    public function open(?int $folderId): void
    {
        $this->folderId = $folderId;
        $this->search = '';
        $this->limit = 60;
        unset($this->folder, $this->folders, $this->files);
    }

    #[Computed]
    public function folder(): ?MediaFolder
    {
        return $this->folderId ? MediaFolder::query()->find($this->folderId) : null;
    }

    #[Computed]
    public function folders(): Collection
    {
        return MediaFolder::query()
            ->where('parent_id', $this->folderId)
            ->withCount(['children', 'files'])
            ->orderByRaw("CASE kind WHEN 'root' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function files(): Collection
    {
        $query = Media::query()->latest('id');

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('path', 'like', $term)->orWhere('alt', 'like', $term));
        } else {
            $query->where('folder_id', $this->folderId);
        }

        return $query->limit($this->limit + 1)->get();
    }

    /** media id => places it is used (products, categories, brands, posts, banners). */
    #[Computed]
    public function usage(): array
    {
        $ids = $this->files->modelKeys();
        if ($ids === []) {
            return [];
        }

        $counts = DB::table('mediables')->whereIn('media_id', $ids)->selectRaw('media_id, COUNT(*) as n')->groupBy('media_id')->pluck('n', 'media_id')->all();
        foreach (['desktop_media_id', 'mobile_media_id'] as $column) {
            foreach (DB::table('banners')->whereIn($column, $ids)->pluck($column) as $id) {
                $counts[$id] = ($counts[$id] ?? 0) + 1;
            }
        }

        return array_map('intval', $counts);
    }

    public function loadMore(): void
    {
        $this->limit += 60;
        unset($this->files);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newFolder')->label('پوشه‌ی جدید')->icon(Heroicon::OutlinedFolderPlus)->color('gray')
                ->schema([TextInput::make('name')->label('نام پوشه')->required()->maxLength(80)])
                ->action(function (array $data) {
                    $folder = app(FolderManager::class)->createCustom($this->folder, $data['name']);
                    $this->open($folder->id);
                }),
            Action::make('upload')->label('آپلود')->icon(Heroicon::OutlinedCloudArrowUp)
                ->disabled(fn () => $this->folder === null)
                ->tooltip(fn () => $this->folder === null ? 'اول یک پوشه را باز کنید' : null)
                ->schema([
                    FileUpload::make('files')->label('تصاویر')->multiple()->image()->maxSize(10240)->storeFiles(false)->required()
                        ->helperText('نام فایل‌ها خودکار از نام پوشه ساخته می‌شود.'),
                ])
                ->action(function (array $data) {
                    $folder = $this->folder;
                    $manager = app(FolderManager::class);
                    $alt = $folder?->product_id ? Product::query()->find($folder->product_id)?->name : null;
                    foreach ($data['files'] as $file) {
                        $manager->store($file, $folder, $alt);
                    }
                    unset($this->files);
                    Notification::make()->title(count($data['files']).' تصویر آپلود شد')->success()->send();
                }),
        ];
    }

    public function renameFolderAction(): Action
    {
        return Action::make('renameFolder')->label('تغییر نام')->icon(Heroicon::OutlinedPencilSquare)->iconButton()->color('gray')
            ->fillForm(fn (array $arguments) => ['name' => MediaFolder::query()->find($arguments['id'])?->name])
            ->schema([TextInput::make('name')->label('نام پوشه')->required()->maxLength(80)])
            ->action(function (array $arguments, array $data) {
                $folder = MediaFolder::query()->findOrFail($arguments['id']);
                abort_if($folder->isSystem(), 403);
                $folder->update(['name' => trim($data['name'])]); // display name only; files keep their addresses
                unset($this->folders);
            });
    }

    public function deleteFolderAction(): Action
    {
        return Action::make('deleteFolder')->label('حذف')->icon(Heroicon::OutlinedTrash)->iconButton()->color('danger')
            ->requiresConfirmation()->modalHeading('حذف پوشه‌ی خالی')
            ->action(function (array $arguments) {
                $folder = MediaFolder::query()->withCount(['children', 'files'])->findOrFail($arguments['id']);
                if ($folder->children_count || $folder->files_count) {
                    Notification::make()->title('فقط پوشه‌ی خالی حذف می‌شود.')->warning()->send();

                    return;
                }
                Storage::disk(FolderManager::DISK)->deleteDirectory($folder->directory());
                $folder->delete();
                unset($this->folders);
            });
    }

    public function deleteFileAction(): Action
    {
        return Action::make('deleteFile')->label('حذف')->icon(Heroicon::OutlinedTrash)->iconButton()->color('danger')
            ->requiresConfirmation()
            ->modalHeading('حذف تصویر')
            ->modalDescription(fn (array $arguments) => ($n = $this->usage[$arguments['id']] ?? 0)
                ? "این تصویر در {$n} جا استفاده شده و از همه‌ی آن‌ها برداشته می‌شود."
                : 'فایل و نسخه‌های WebP آن پاک می‌شوند.')
            ->action(function (array $arguments) {
                $media = Media::query()->findOrFail($arguments['id']);
                DB::table('mediables')->where('media_id', $media->id)->delete();
                Storage::disk($media->disk)->delete($media->path);
                $media->delete();
                unset($this->files, $this->usage);
                Notification::make()->title('تصویر حذف شد')->success()->send();
            });
    }
}
