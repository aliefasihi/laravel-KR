<?php

/**
 * Khaneh Romantic — «ربات بله و تلگرام» page: people + connection status + connect (webhook / polling)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Filament\Resources\Operators\Pages;

use App\Core\Settings\Settings;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Modules\Bot\Filament\Resources\Operators\BotOperatorResource;
use Modules\Bot\Support\Channel;
use Modules\Bot\Support\ProductAi;

class ManageBotOperators extends ManageRecords
{
    protected static string $resource = BotOperatorResource::class;

    /** the bot's @username (asked once, kept 12 hours) */
    public static function username(Channel $channel): ?string
    {
        return Cache::remember('bot.username.'.$channel->name.'.'.md5($channel->token()), now()->addHours(12),
            fn () => $channel->me()['username'] ?? null) ?: null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        $parts = [];
        foreach (Channel::NAMES as $name => $label) {
            $channel = Channel::get($name);
            $parts[] = $channel->enabled()
                ? $label.': '.(($u = Cache::get('bot.username.'.$name.'.'.md5($channel->token()))) ? '@'.$u.' ✓' : 'روشن')
                : $label.': خاموش';
        }
        $parts[] = 'دریافت: '.(Settings::get('bot.mode', 'webhook') === 'polling' ? 'پرسیدن مداوم' : 'وب‌هوک');
        $parts[] = 'هوش مصنوعی: '.(ProductAi::available() ? 'روشن' : 'خاموش (قدم‌به‌قدم)');

        return new HtmlString(e(implode(' · ', $parts)));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect')->label('اتصال / بررسی')->icon('heroicon-o-signal')->color('gray')
                ->action(function () {
                    $channels = Channel::all();
                    if ($channels === []) {
                        Notification::make()->warning()->title('اول توکن ربات را در «تنظیمات فروشگاه ← ربات بله و تلگرام» بگذارید و روشن کنید')->send();

                        return;
                    }
                    $polling = Settings::get('bot.mode', 'webhook') === 'polling';
                    $lines = [];
                    foreach ($channels as $channel) {
                        Cache::forget('bot.username.'.$channel->name.'.'.md5($channel->token()));
                        $me = self::username($channel);
                        if (! $me) {
                            $lines[] = $channel->label().': وصل نشد (توکن'.($channel->name === 'telegram' ? '، پراکسی' : '').' یا اینترنت سرور)';

                            continue;
                        }
                        $ok = $polling ? $channel->deleteWebhook() : $channel->setWebhook();
                        $lines[] = $channel->label().': @'.$me.' — '.($polling ? 'آماده‌ی پرسیدن مداوم' : ($ok ? 'وب‌هوک تنظیم شد' : 'وب‌هوک تنظیم نشد (آدرس سایت باید https باشد)'));
                    }
                    Notification::make()->title('ربات')->body(implode("\n", $lines))->persistent()->send();
                }),
            CreateAction::make()->label('افزودن فرد'),
        ];
    }
}
