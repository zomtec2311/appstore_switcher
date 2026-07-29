<?php
return [
    'routes' => [
        ['name' => 'admin#switchStore', 'url' => '/switch', 'verb' => 'POST'],
        ['name' => 'admin#getUrlHistory', 'url' => '/history', 'verb' => 'GET'],
        ['name' => 'admin#removeFromHistory', 'url' => '/history/remove', 'verb' => 'POST'],
    ]
];
