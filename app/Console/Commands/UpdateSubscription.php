<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateSubscription extends Command
{
    /**
     * The name and signature of the console command.
     * コマンド名
     * @var string
     */
    protected $signature = 'subscriptions:update-subscriptions';

    /**
     * The console command description.
     * コマンドの説明
     * @var string
     */
    protected $description = '毎日0:00にnext_payment_dayが本日の日付のsubscriptionを更新し、number_of_paymentsを+1する';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * コンソールコマンドの実行
     * @return int
     */
    public function handle()
    {
        $today = Carbon::today();
        $subscriptions = Subscription::whereDate('next_payment_day', $today)->get();

        if($subscriptions->isEmpty()) {
            $this->info('本日更新するSubscriptionはありません。');
            Log::info('UpdateSubscriptionバッチ：本日更新対象なし。');
            return Command::SUCCESS;
        }

        Log::info("UpdateSubscriptionバッチ：対象件数 = {$subscriptions->count()}");

        DB::beginTransaction();

        try {
            foreach($subscriptions as $subscription){
                $subscription->next_payment_day = Carbon::parse($subscription->next_payment_day)->addMonthNoOverflow($subscription->frequency);
                $subscription->number_of_payments += 1;
                $subscription->save();
                $this->info("Subscription ID {$subscription->id}を更新しました。");
            }
            DB::commit();
            $this->info('処理が終了しました。');
            Log::info('UpdateSubscriptionバッチが正常に完了しました。');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("エラーが発生しました：" . $e->getMessage());
            Log::error('UpdateSubscriptionバッチでエラーが発生', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return Command::SUCCESS;    // 省略可能
    }
}
