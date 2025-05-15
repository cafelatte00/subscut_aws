<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Mail\ReminderMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendReminderEmails extends Command
{
    // コマンド名
    protected $signature = 'emails:send-reminders';
    // コマンドの説明
    protected $description = '支払日が近いユーザーにリマインドメールを送る';

    public function handle()
    {
        // 明日から１週間以内にnext_Payment_dayがあるサブスクを持つユーザーを取得
        $users = User::whereHas('subscriptions', function($query){
            $query->whereBetween('next_payment_day', [Carbon::tomorrow(), Carbon::now()->addWeek()]);
        })->get();

        if($users->isEmpty()) {
            Log::info('ReminderMail: 今日の送信対象のユーザーはいませんでした。');
            return;
        }

        foreach($users as $user){
            // 明日から１週間以内のnext_payment_dayがあるサブスクサブ全てを取得
            $subscriptions = $user->subscriptions()->whereBetween('next_payment_day', [Carbon::tomorrow(), Carbon::now()->addWeek()])->get();

            try {
                // ユーザーにメール送信
                Mail::to($user->email)->send(new ReminderMail($user, $subscriptions));
                Log::info("RemindMail: {$user->email}にリマインドメールを送信しました。");
            } catch (\Exception $e) {
                // メール送信失敗ログ
                Log::error("{$user->email}へのメール送信に失敗しました。", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        $this->info('リマインドメールを送信しました。');
    }
}
