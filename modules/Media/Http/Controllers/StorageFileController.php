<?php

/**
 * Khaneh Romantic — /storage/… when the public/storage link is missing (fresh clone, a new
 * computer, a server where `php artisan storage:link` wasn't run): uploaded images still show.
 * The web server answers these itself while the link exists, so this costs nothing then.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Media\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFileController
{
    public function __invoke(string $path): BinaryFileResponse
    {
        $root = realpath(storage_path('app/public'));
        $file = $root ? realpath($root.DIRECTORY_SEPARATOR.$path) : false;

        abort_unless($file && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file), 404);

        return response()->file($file, ['Cache-Control' => 'public, max-age=2592000']);
    }
}
