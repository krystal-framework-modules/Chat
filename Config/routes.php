<?php

return array(
    '/profile/chat' => array(
        'controller' => 'Chat@indexAction'
    ),

    '/profile/chat/send' => array(
        'controller' => 'Chat@sendAction'
    ),

    '/profile/chat/dialog/(:var)' => array(
        'controller' => 'Chat@dialogAction'
    ),

    '/profile/chat/delete/(:var)' => array(
        'controller' => 'Chat@deleteAction'
    )
);
