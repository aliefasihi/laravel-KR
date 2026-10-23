<?php

/**
 * Khaneh Romantic — Site chat (چت‌بات سایت) module
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Providers;

use App\Core\Modules\Modules;
use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Chat\Models\ChatConversation;
use Modules\Chat\Support\Chat;
use Modules\Crm\Support\FormDefinition;
use Modules\Crm\Support\Forms;

class ChatServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        StorefrontHooks::add('body.end', fn () => Chat::onPage(request()->route()?->getName()) ? view('chat::partials.widget') : '', 70);

        if (class_exists(Forms::class) && Modules::enabled('Crm')) {
            Forms::define(new FormDefinition('chat_callback', 'درخواست تماس از چت', ['mobile' => 'موبایل', 'about' => 'موضوع'],
                icon: 'heroicon-o-phone-arrow-down-left', sort: 33));
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // short guest chats that went nowhere don't need to stay forever
            $schedule->call(fn () => ChatConversation::query()->where('last_message_at', '<', now()->subDays(180))->delete())->weekly()->name('chat:prune');
        });

        Settings::section(new SettingsSection(
            key: 'chat',
            title: 'چت‌بات سایت',
            icon: 'heroicon-o-chat-bubble-oval-left-ellipsis',
            sort: 62,
            defaults: ['enabled' => false, 'name' => null, 'greeting' => null, 'hours' => null, 'ai' => true, 'notify_bot' => true],
            schema: fn () => [
                Toggle::make('enabled')->label('چت‌بات روی سایت باشد')
                    ->helperText('دکمه‌ی گفتگو جای دکمه‌ی واتس‌اپ می‌نشیند (واتس‌اپ داخل گفتگو هست). در صفحه‌ی پرداخت نشان داده نمی‌شود.'),
                TextInput::make('name')->label('نام دستیار')->placeholder('دستیار خانه رمانتیک')->maxLength(40),
                Textarea::make('greeting')->label('پیام خوش‌آمد')->rows(2)->maxLength(300)
                    ->placeholder('سلام! من دستیار خانه رمانتیک هستم 🌸 سفارش‌تان را پیگیری کنم، محصولی پیدا کنم یا سؤالی دارید؟'),
                TextInput::make('hours')->label('ساعت کاری (برای جواب «ساعت کاری؟»)')->placeholder('شنبه تا پنجشنبه ۱۰ تا ۲۰')->maxLength(100),
                Toggle::make('ai')->label('سؤال‌های آزاد با هوش مصنوعی جواب داده شوند')
                    ->helperText('فقط از اطلاعات خود فروشگاه (ارسال، مرجوعی، پرداخت، جواب‌های آماده، محصولات پیداشده). نیاز به روشن بودن «هوش مصنوعی» دارد؛ وگرنه لایه‌ی پایه جواب می‌دهد.'),
                Toggle::make('notify_bot')->label('درخواست پشتیبان و شماره‌ی تماس در ربات بله/تلگرام خبر داده شود'),
            ],
        ));
    }
}
