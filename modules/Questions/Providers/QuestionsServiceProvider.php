<?php

/**
 * Khaneh Romantic — Product questions & answers
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Questions\Providers;

use App\Core\Modules\Modules;
use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Livewire\Livewire;
use Modules\Crm\Support\FormDefinition;
use Modules\Crm\Support\Forms;
use Modules\Questions\Filament\Resources\Questions\QuestionResource;
use Modules\Questions\Livewire\QuestionsSection;

class QuestionsServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'qa';
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('qa.section', QuestionsSection::class);
        StorefrontHooks::add('product.tabs', 'qa::partials.tab', 60);
        StorefrontHooks::add('product.sections', 'qa::partials.section', 60);

        if (class_exists(Forms::class) && Modules::enabled('Crm')) {
            Forms::define(new FormDefinition('product_question', 'پرسش محصول', [
                'name' => 'نام', 'mobile' => 'موبایل', 'product' => 'محصول', 'body' => 'پرسش',
            ], icon: 'heroicon-o-question-mark-circle', sort: 31, subjectUrl: fn () => QuestionResource::getUrl()));
        }

        Settings::section(new SettingsSection(
            key: 'questions',
            title: 'پرسش و پاسخ',
            icon: 'heroicon-o-question-mark-circle',
            sort: 41,
            defaults: ['allow_guests' => true, 'answered_template' => null],
            schema: fn () => [
                Toggle::make('allow_guests')->label('بازدیدکننده‌های بدون ورود هم بتوانند سؤال بپرسند (با نام؛ موبایل اختیاری)'),
                TextInput::make('answered_template')->label('الگوی پیامک «جواب سؤالتان آمد»')->extraInputAttributes(['dir' => 'ltr'])
                    ->helperText('نام الگو (کاوه‌نگار) یا شناسه‌ی قالب (sms.ir). مقادیر: PRODUCT (نام محصول؛ کاوه‌نگار token)، LINK (لینک پرسش‌ها؛ token2). نمونه: جواب سؤال شما درباره‌ی %token% آمد: %token2%'),
            ],
        ));
    }
}
