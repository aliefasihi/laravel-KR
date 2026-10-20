<?php

/**
 * Khaneh Romantic — One-line postal address for anything with province/city/address/plaque/unit
 * columns (a saved address, an order). «تهران، تهران، ونک، پلاک ۱۲، واحد ۳»
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Concerns;

trait HasPostalAddress
{
    public function addressLine(): string
    {
        return collect([
            $this->province.'، '.$this->city,
            $this->address,
            $this->plaque ? 'پلاک '.$this->plaque : null,
            $this->unit ? 'واحد '.$this->unit : null,
        ])->filter()->implode('، ');
    }
}
