# SubsCut 〜サブスカット〜（サブスク管理アプリ）

個人開発で作成した、**サブスクリプション管理アプリ**です。
毎月の支出を可視化し、ムダなサブスクを見直す手助けをします。

解説記事（Qiita）: [【ポートフォリオ】Laravel + AWS + jQueryで構築したサブスク管理アプリ「SubsCut」の解説](https://qiita.com/latte00/items/60d2d44e81b73f1f48c7)

---

##  アプリURL（本番環境）

[https://subscut.site/](https://subscut.site/)

-  **ゲストログイン機能あり**
  - メールアドレスや登録不要ですぐにお試しできます。
  - 「ゲストログイン」ボタンを押すと、ログインできます。

---

##  主な機能

- サブスクの登録・一覧表示・詳細表示・編集・削除（登録はAjax）
- 月額支出のグラフ表示（Chart.js）
- アイコン画像のアップロード・削除（Amazon S3）
- メール通知機能（Gmail SMTP）
- 日次バッチ処理による状態自動更新
- レスポンシブ対応（スマホ表示OK）

---

##  使用技術スタック

| 項目 | 内容 |
|------|------|
| バックエンド | PHP 8 / Laravel 9 |
| フロントエンド | JavaScript / jQuery / Chart.js |
| メールサーバー | Gmail SMTP |
| インフラ | AWS（EC2 / RDS[MySQL] / S3 / Route 53 / ACM / IAM） |
