<?php

/**
 * Khaneh Romantic — Spam guard for public Livewire forms (contact, comments, reviews):
 * a hidden «website» field people never fill, and a minimum time between showing the form
 * and sending it. Put <x-ui.honeypot /> inside the form and call $this->looksLikeBot().
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Core\Livewire;

use Livewire\Attributes\Locked;

trait StopsBots
{
    public string $website = '';   // honeypot

    #[Locked]
    public int $startedAt = 0;

    public function mountStopsBots(): void
    {
        $this->startedAt = now()->getTimestamp();
    }

    /** Filled the hidden field, or sent faster than a person can type. */
    protected function looksLikeBot(int $minSeconds = 3): bool
    {
        return $this->website !== '' || now()->getTimestamp() - $this->startedAt < $minSeconds;
    }
}
