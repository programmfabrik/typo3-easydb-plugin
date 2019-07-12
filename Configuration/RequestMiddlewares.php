<?php
/**
 * An array consisting of implementations of middlewares for a middleware stack to be registered
 *
 *  'stackname' => [
 *      'middleware-identifier' => [
 *         'target' => classname or callable
 *         'before/after' => array of dependencies
 *      ]
 *   ]
 */
return [
    'backend' => [
        'easydb/cors' => [
            'target' => \Easydb\Typo3Integration\Backend\Middleware\CorsMiddleware::class,
            'before' => [
                'typo3/cms-backend/authentication'
            ],
            'after' => [
                'typo3/cms-backend/backend-routing'
            ],
        ],
    ]
];
