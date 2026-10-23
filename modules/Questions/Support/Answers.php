<?php

/**
 * Khaneh Romantic — Answering a question: it goes live under the product, the asker gets an SMS
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * SMS template (تنظیمات ← پرسش و پاسخ), values: PRODUCT (name), LINK (the product's questions).
 */

namespace Modules\Questions\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use Illuminate\Support\Facades\Log;
use Modules\Questions\Models\Question;
use Modules\Sms\Support\Sms;
use Throwable;

final class Answers
{
    public function answer(Question $question, string $answer, ?int $userId = null): void
    {
        $first = $question->answer === null;
        $question->update([
            'answer' => trim($answer),
            'answered_by' => $userId,
            'answered_at' => now(),
            'status' => 'published',
        ]);

        if ($first && $question->notify && $question->mobile) {
            $this->notify($question);
        }
    }

    private function notify(Question $question): void
    {
        $template = Settings::get('questions.answered_template');
        if (! $template || ! Modules::enabled('Sms') || ! $question->product) {
            return;
        }
        $values = [mb_substr($question->product->name, 0, 60), $question->product->url().'?utm_source=sms#questions'];
        try {
            app(Sms::class)->template($question->mobile, (string) $template, $values);   // the admin waits a moment; the shopper never does
        } catch (Throwable $e) {
            Log::warning('Question SMS failed: '.$e->getMessage());
        }
    }
}
