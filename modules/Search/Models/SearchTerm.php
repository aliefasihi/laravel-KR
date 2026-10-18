<?php

/**
 * Khaneh Romantic — What shoppers searched for (one row per normalized term)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SearchTerm extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'searches' => 'integer',
            'last_results' => 'integer',
            'zero_results' => 'integer',
            'last_searched_at' => 'datetime',
        ];
    }

    /** Count one search (safe under concurrent requests: increments happen in SQL). */
    public static function record(string $term, int $results): void
    {
        $now = Carbon::now();
        $values = [
            'searches' => DB::raw('searches + 1'),
            'zero_results' => DB::raw('zero_results + '.($results === 0 ? 1 : 0)),
            'last_results' => $results,
            'last_searched_at' => $now,
        ];

        if (static::query()->where('term', $term)->update($values) === 0) {
            static::query()->insertOrIgnore(['term' => $term, 'created_at' => $now, 'updated_at' => $now]);
            static::query()->where('term', $term)->update($values);
        }
    }
}
