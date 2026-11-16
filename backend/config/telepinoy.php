<?php

return [
    'admin_email' => env('TELEPINOY_ADMIN_EMAIL'),
    'admin_password' => env('TELEPINOY_ADMIN_PASSWORD'),
    'playlist_hosts' => array_filter(array_map(
        'trim',
        explode(',', env('TELEPINOY_PLAYLIST_HOSTS', 'iptv-org.github.io'))
    )),
];
