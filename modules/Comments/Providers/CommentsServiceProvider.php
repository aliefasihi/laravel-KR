<?php

/**
 * Khaneh Romantic — Comments under blog posts
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Adds the comments section after every post (hook «post.after»), moderation in the admin, the
 * «نظر مقاله» tab of the forms inbox and the settings tab «نظرات مقاله‌ها».
 */

namespace Modules\Comments\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Livewire\Livewire;
use Modules\Comments\Filament\Resources\Comments\CommentResource;
use Modules\Comments\Livewire\CommentsSection;
use Modules\Crm\Support\FormDefinition;
use Modules\Crm\Support\Forms;

class CommentsServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Livewire::component('comments.section', CommentsSection::class);
        StorefrontHooks::add('post.after', 'comments::partials.after-post');

        Forms::define(new FormDefinition('post_comment', 'نظر مقاله', [
            'name' => 'نام', 'mobile' => 'موبایل', 'email' => 'ایمیل', 'post' => 'مقاله', 'reply_to' => 'در پاسخ به', 'body' => 'متن',
        ], icon: 'heroicon-o-chat-bubble-left-right', sort: 40, subjectUrl: fn () => CommentResource::getUrl()));

        Settings::section(new SettingsSection(
            key: 'comments',
            title: 'نظرات مقاله‌ها',
            icon: 'heroicon-o-chat-bubble-left-right',
            sort: 41,
            defaults: ['moderation' => 'all', 'require_login' => false, 'ask_mobile' => 'optional'],
            schema: fn () => [
                Radio::make('moderation')->label('انتشار نظر')->options([
                    'all' => 'همه پس از تأیید در پنل',
                    'customers' => 'نظر مشتریانِ واردشده خودکار؛ بقیه پس از تأیید',
                    'none' => 'همه خودکار',
                ])->required(),
                Toggle::make('require_login')->label('فقط کاربران واردشده بتوانند نظر بنویسند'),
                Radio::make('ask_mobile')->label('شماره‌ی موبایل نویسنده')->options([
                    'optional' => 'اختیاری (برای پاسخ و مخاطبان)', 'required' => 'اجباری', 'hidden' => 'پرسیده نشود',
                ])->inline()->required(),
            ],
        ));
    }
}
