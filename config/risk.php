<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Default Risk Scoring Engine Configuration
    |--------------------------------------------------------------------------
    |
    | Based on Algorithm 3.8.7 and Section 4.7 of the project report:
    | Four-component scale with a maximum potential score of 85 points.
    |
    */

    'max_score' => 85,

    'classification' => [
        'low' => [
            'min' => 0,
            'max' => 28,
        ],
        'medium' => [
            'min' => 29,
            'max' => 56,
        ],
        'high' => [
            'min' => 57,
            'max' => 85,
        ],
    ],

    // Component A: Historical late-payment rate (Employee-level)
    'component_a' => [
        'tier_1' => ['threshold' => 10, 'points' => 0],
        'tier_2' => ['threshold' => 30, 'points' => 10],
        'tier_3' => ['threshold' => 50, 'points' => 20],
        'tier_4' => ['points' => 30],
        'no_history_points' => 0,
    ],

    // Component B: Currently overdue obligations count (Employer-level, excluding current)
    'component_b' => [
        'zero' => ['count' => 0, 'points' => 0],
        'one' => ['count' => 1, 'points' => 15],
        'multiple' => ['count' => 2, 'points' => 25],
    ],

    // Component C: Outstanding overdue balance ratio (Employer-level, excluding current)
    'component_c' => [
        'zero' => ['ratio' => 0, 'points' => 0],
        'tier_1' => ['threshold' => 25, 'points' => 5],
        'tier_2' => ['threshold' => 50, 'points' => 10],
        'tier_3' => ['points' => 15],
    ],

    // Component D: Due date proximity (Obligation-level)
    'component_d' => [
        'distant' => ['min_days' => 8, 'points' => 0],
        'approaching' => ['min_days' => 4, 'max_days' => 7, 'points' => 5],
        'imminent' => ['min_days' => 1, 'max_days' => 3, 'points' => 10],
        'due_or_overdue' => ['max_days' => 0, 'points' => 15],
    ],
];
