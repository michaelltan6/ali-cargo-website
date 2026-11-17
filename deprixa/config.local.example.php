<?php
// Salin jadi config.local.php di server, lalu isi data database.
return array(
    'dsn'  => 'mysql:host=localhost;dbname=NAMA_DB;charset=utf8mb4',
    'user' => 'USER_DB',
    'pass' => 'PASSWORD_DB',
    // Email pengingat jatuh tempo (opsional, isi kalau mau email otomatis aktif)
    'mail' => array(
        'host'      => 'smtp.hostinger.com',
        'port'      => 465,
        'secure'    => 'ssl',                       // 'ssl' untuk 465, 'tls' untuk 587
        'user'      => 'noreply@domainmu.com',
        'pass'      => 'PASSWORD_EMAIL',
        'from'      => 'noreply@domainmu.com',
        'from_name' => 'PT. Aneka Lintas Indonesia Cargo',
        'reply_to'  => 'finance@domainmu.com',      // balasan customer masuk ke sini
        'cc'        => 'finance@domainmu.com',      // salinan setiap pengingat (boleh beberapa, pisah koma)
    ),
);
