<?php
// 1) 複製此檔案為 config.php
// 2) 依你的環境填入 DB 與 SMTP 設定

return [
    // 注意：為了支援「自動建立資料庫」，
    // dbname 會由程式自動建立/使用（預設 mailtest）
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'mailtest',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    'smtp' => [
        'host' => 'smtp.gmail.com',
        'username' => '',
        'password' => '',
        'port' => 465,
        'secure' => 'ssl', // 'ssl' 或 'tls'

        'from_email' => '',
        'from_name' => 'Mailer',
    ],
];

