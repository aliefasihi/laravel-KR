<?php

/**
 * Khaneh Romantic — Chunked upload of a WordPress database dump (admin only)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The page sends the file in pieces (raw body, ?offset=) so php.ini/nginx upload limits don't
 * matter; a piece at the wrong offset gets 409 with the right one, so uploads resume.
 */

namespace Modules\WordPressImport\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\WordPressImport\Support\Dump\Sources;
use Throwable;

class DumpUploadController
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()?->is_admin, 403);

        $data = $request->validate([
            'upload' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{8,64}$/'],
            'name' => ['required', 'string', 'max:200', 'regex:'.Sources::EXTENSIONS],
            'size' => ['required', 'integer', 'min:1'],
            'offset' => ['required', 'integer', 'min:0'],
        ], ['name.regex' => 'فقط فایل‌های .sql، .sql.gz و .zip پذیرفته می‌شوند.']);

        $dir = Storage::disk('local')->path('wp-imports/uploads');
        File::ensureDirectoryExists($dir);
        $part = $dir.'/'.$data['upload'].'.part';
        $current = is_file($part) ? filesize($part) : 0;

        if ((int) $data['offset'] !== $current) {
            return response()->json(['offset' => $current], 409);
        }
        if ($current === 0 && ($free = @disk_free_space($dir)) !== false && $free < (int) $data['size'] * 3) {
            return response()->json(['message' => 'فضای خالی دیسک کافی نیست (حدود سه برابر حجم فایل لازم است).'], 507);
        }

        $in = $request->getContent(true);   // the raw body as a stream (php://input)
        $out = fopen($part, 'ab');
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);
        clearstatcache(true, $part);
        $size = filesize($part);

        if ($size < (int) $data['size']) {
            return response()->json(['offset' => $size]);
        }

        try {
            $source = Sources::fromFile($part, basename($data['name']), $request->user()->id);
        } catch (Throwable $e) {
            @unlink($part);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['done' => true, 'id' => $source->id, 'offset' => $size]);
    }
}
