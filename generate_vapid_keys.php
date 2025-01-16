<?php
require 'vendor/autoload.php'; // Load Composer dependencies

use Minishlink\WebPush\VAPID;

$keys = VAPID::createVapidKeys();
echo "Public Key: {$keys['publicKey']}\n";
echo "Private Key: {$keys['privateKey']}\n";
