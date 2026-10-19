<?php

/**
 * Khaneh Romantic — A form the inbox knows: its tab, its fields (columns) and where its records live
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Crm\Support;

use Closure;
use Modules\Crm\Models\FormSubmission;

final class FormDefinition
{
    /**
     * @param  array<string, string>  $fields  data key => label (table columns and export headers, in order)
     * @param  ?Closure(FormSubmission): ?string  $subjectUrl  admin link to the related record
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $fields,
        public readonly string $icon = 'heroicon-o-document-text',
        public readonly int $sort = 50,
        public readonly ?Closure $subjectUrl = null,
        public readonly bool $alert = true,     // offered in «SMS for new submissions»
    ) {}
}
