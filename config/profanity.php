<?php

/**
 * Content moderation: blocked words reject the message; censored words are masked when stored.
 * Expand these lists for your community standards (keep entries lowercase).
 */
return [

    'blocked' => [
        // Hate/slurs placeholders — replace/extend with a fuller list in production
    ],

    'censored' => [
        'damn',
        'hell',
        'crap',
        'stupid',
        'idiot',
        'moron',
        'fuck',
        'shit',
        'bitch',
        'asshole',
        'bastard',
        'dick',
        'cunt',
        'pussy',
        'whore',
        'slut',
    ],

];
