# SubsCut 〜サブスカット〜（サブスク管理アプリ）
[https://subscut.site/](https://subscut.site/)

<img width="1456" alt="Image" src="https://github.com/user-attachments/assets/83e53cf0-dbfa-4f72-9f9f-71ecf7ff8732" />
個人開発で作成した、<b>サブスク管理アプリ</b>です。
毎月の支出を可視化し、ムダなサブスクを見直す手助けをします。

#### 解説記事（Qiita）
画像付きの説明で、アプリを使用せず概要を掴んでいただけます。  
[【ポートフォリオ】Laravel + AWS + jQueryで構築したサブスク管理アプリ「SubsCut」の解説](https://qiita.com/latte00/items/60d2d44e81b73f1f48c7)


---

##  アプリURL（本番環境）

[https://subscut.site/](https://subscut.site/)

-  **ゲストログイン機能あり**
  - メールアドレス登録不要ですぐにお試しできます。
  - トップページの「ゲストログイン」ボタンを押すと、すぐにログインできます。

---

##  主な機能

- サブスクの登録・一覧表示・詳細表示・編集・削除（登録はAjax）
- 月額サブスク支出データのグラフ描画（Chart.js × Laravel API）
- アイコン画像のアップロード・削除（Amazon S3）
- メール通知機能（Gmail SMTP）
- 日次バッチ処理による状態自動更新、リマインドメール送信
- レスポンシブ対応（スマホ表示OK）

---

##  使用技術スタック

| 項目 | 内容 |
|------|------|
| バックエンド | PHP 8 / Laravel 9 （REST API実装）|
| フロントエンド | JavaScript / jQuery / Chart.js （API連携によるグラフ表示）|
| バッチ処理     | Laravel Scheduler + Cron（EC2上で定期実行）
| メールサーバー | Gmail SMTP |
| インフラ | AWS（EC2 / RDS[MySQL] / S3 / Route 53 / ACM / IAM） |

---

## サブスク支出チャート機能（API + Chart.js）
<img width="1453" alt="Image" src="https://github.com/user-attachments/assets/20ef089c-5d1b-44d7-9bff-dc34ed981c40" />
月ごとのサブスク支出を集計し、Chart.jsで可視化する機能を実装しました。  
Laravel側では、REST API形式で集計データをJSONレスポンスとして返却し、JavaScriptから非同期で取得しています。

- GET `/subscriptions/chart-data`：月別支出を返す**REST APIエンドポイント**
- `/subscriptions/chart`：Chart.jsによるグラフ描画用の**画面ビュー**
- 支出のない月は0円で補完、最大24ヶ月分を表示

