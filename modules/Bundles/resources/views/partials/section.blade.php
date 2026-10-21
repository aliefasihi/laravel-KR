{{-- product.sections hook → sets and «bought together» (renders nothing when there are none) | نویسنده: علی فصیحی (Ali Fasihi) --}}
<livewire:bundles.product-offers :product="$product" :key="'bundles-'.$product->id" />
