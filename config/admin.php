<?php

return [
    'usernames' => array_map('strtolower', array_filter(explode(',', env('ADMIN_USERNAMES', 'admin,sinta')))),
];

