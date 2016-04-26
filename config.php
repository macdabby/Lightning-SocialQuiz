<?php

return [
    'package' => [
        'module' => 'SocialQuiz',
        'version' => '1.0',
    ],
    'routes' => [
        'static' => [
            'quiz' => 'Modules\\SocialQuiz\\Pages\\Quiz',
            'quiz/results' => 'Modules\\SocialQuiz\\Pages\\AggregatedResults',
        ]
    ],
];
