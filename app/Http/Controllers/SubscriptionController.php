<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription;
use App\Services\CheckSubscriptionService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreSubscriptionRequest;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $subscriptions = Subscription::latest()->where('user_id', '=', $user->id)->paginate(5);

        // サブスクリプションごとに支払い情報を更新
        foreach ($subscriptions as $subscription) {
            $this->updateSubscriptionPaymentDetails($subscription);   // SubscriptionController のインスタンス
        }

        return view('subscriptions.index', compact('subscriptions', 'user'));
    }

    // モーダルFormからの新規保存 (Ajax)
    public function addSubscription(StoreSubscriptionRequest $request)
    {
        $user = Auth::user();
        $firstPaymentDay = Carbon::parse($request->first_payment_day); // 初回支払日
        $frequency = $request->frequency;

        $paymentDetails =CheckSubscriptionService::calculatePaymentDetails($firstPaymentDay, $frequency);

        Subscription::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'price' => $request->price,
            'frequency' => $request->frequency,
            'first_payment_day' => $firstPaymentDay,
            'next_payment_day' => $paymentDetails['nextPaymentDay'],
            'number_of_payments' => $paymentDetails['numberOfPayments'],
            'url' => $request->url,
            'memo' => $request->memo,
        ]);
        $new_subscription = Subscription::where('user_id','=',$user->id)->orderByDesc('id')
        ->first();
        $title = $new_subscription->title;

        // JSONレスポンスを返す
        return response()->json([
            'new_subscription'=>$new_subscription,   // フラッシュメッセージを返す
        ]);
    }

    public function show($id)
    {
        $subscription = Subscription::find($id);
        $user = auth()->user(); //アクセスしているユーザ情報を取得

        if($user->can('view',$subscription)){
            // 表示前に支払い情報を更新
            $this->updateSubscriptionPaymentDetails($subscription);
            $frequency = CheckSubscriptionService::checkFrequency($subscription);
            return view('subscriptions.show',compact('subscription', 'frequency'));
        }else{
            return '閲覧権限がありません。';
        }
    }

    public function edit($id)
    {
        $subscription = Subscription::find($id);
        $user = auth()->user();

        if($user->can('update',$subscription)){
            return view('subscriptions.edit', compact('subscription'));
        }else{
            return '閲覧権限がありません。';
        }
    }

    public function update(StoreSubscriptionRequest $request, $id)
    {
        $user = auth()->user();
        $subscription = Subscription::find($id);
        $originalSubscription = clone $subscription; // 変更前のサブスク情報
        $originalSubscription->first_payment_day = Carbon::parse($originalSubscription->first_payment_day);

        if($user->can('update',$subscription)){

            $subscription->title = $request->title;
            $subscription->price = $request->price;
            $subscription->frequency = $request->frequency;
            $subscription->first_payment_day = Carbon::parse($request->first_payment_day);
            $subscription->url = $request->url;
            $subscription->memo = $request->memo;

            // 初回支払日と支払い頻度に変更があれば、初回支払日・次回支払日・支払い回数を確認する
            if($originalSubscription->first_payment_day->ne($subscription->first_payment_day) || $originalSubscription->frequency !== (int)$subscription->frequency){

                $paymentDetails =CheckSubscriptionService::calculatePaymentDetails($subscription->first_payment_day, $subscription->frequency);
                $subscription->next_payment_day = $paymentDetails['nextPaymentDay'];
                $subscription->number_of_payments = $paymentDetails['numberOfPayments'];
            }

            $subscription->save();

            return to_route('subscriptions.show', ['id' => $id])->with('status', 'サブスクを更新しました。');
        }else{
            abort(403);
        }
    }

    public function delete(Request $request, $id)
    {
        $subscription = Subscription::find($id);
        $user = auth()->user(); //アクセスしているユーザ情報を取得

        if($user->can('delete',$subscription)){
            $subscription->delete();
            return to_route('subscriptions.index')->with('status', 'サブスクを1件削除しました。');
        }else{
            abort(403);
        }
    }

    public function cancel(Request $request, $subscription)
    {
        $subscription = Subscription::find($subscription);

        if($subscription->user_id !== auth()->id()){
            abort(403, '権限がありません');
        }

        $subscription->cancel_day = Carbon::today();
        $subscription->save();

        return to_route('subscriptions.show', ['id' => $subscription->id])->with('status', 'サブスクを解約しました。');
    }

    public function chart()
    {
        return view('subscriptions.chart');
    }

        public function getChartData()
    {
        $user = auth()->user();
        $subscriptions = Subscription::where('user_id', $user->id)->get();

        $oldestDate = $subscriptions->min('first_payment_day');
        $startDate = $oldestDate ? Carbon::parse($oldestDate) : Carbon::now();
        $endDate = Carbon::now();

        $monthlyTotals = [];

        // $subscriptionsが空の場合、ループはスキップされる
        foreach ($subscriptions as $subscription) {
            $currentDate = Carbon::parse($subscription->first_payment_day);
            $cancelDate =$subscription->cancel_day ? Carbon::parse($subscription->cancel_day) : null;

            // 解約日が課金日より前なら、このサブスクは記録しない
            if($cancelDate && $cancelDate->lt($currentDate)){
                continue;
            }
            while ($currentDate->lte($endDate)) {
                // 解約日が設定されている場合は、その月は課金を記録し、それ以降は停止
                if ($cancelDate && $currentDate->gte($cancelDate)) {
                    break;
                }

                $month = $currentDate->format('Y-m');
                if (!isset($monthlyTotals[$month])) {
                    $monthlyTotals[$month] = 0;
                }
                $monthlyTotals[$month] += $subscription->price;
                $currentDate->addMonths($subscription->frequency);
            }
        }

        $labels = [];
        $data = [];
        while ($startDate->lte($endDate)) {
            $month = $startDate->format('Y-m');
            $labels[] = $month;
            $data[] = $monthlyTotals[$month] ?? 0;    // その月のサブスク支払い金額が無ければ0を入れる
            $startDate->addMonth();
        }

        // ラベルやデータが25ヶ月以上だったら、24ヶ月分にする
        if (count($labels) > 24) {
            $labels = array_slice($labels, -24);
            $data = array_slice($data, -24);
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    // サブスクの支払い情報を最新の状態に保つ。
    private function updateSubscriptionPaymentDetails(Subscription $subscription)
    {
        $today = Carbon::today();

        // キャンセル日があり、かつ今日より過去なら更新しない
        if($subscription->cancel_day && Carbon::parse($subscription->cancel_day)->lt($today)){
            return;
        }

        $paymentDetails = CheckSubscriptionService::calculatePaymentDetails(
            Carbon::parse($subscription->first_payment_day),
            $subscription->frequency
        );

        $subscription->next_payment_day = $paymentDetails['nextPaymentDay'];
        $subscription->number_of_payments = $paymentDetails['numberOfPayments'];
        $subscription->save();
    }
}
