<?php

return [
    'username' => env('GBIF_USERNAME'),
    'password' => env('GBIF_PASSWORD'),
    'notification_email' => env('GBIF_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS')),
];
