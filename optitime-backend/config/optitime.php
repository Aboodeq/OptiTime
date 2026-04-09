<?php

return [
    'default_schedule_settings_path' => database_path('data/default_schedule_settings.json'),

    'backtracking' => [
        'max_backtracks' => 100_000,
        'max_seconds' => 25.0,
        'max_recursive_steps' => 500_000,
    ],

    'genetic' => [
        'population_size' => 80,
        'max_generations' => 200,
        'max_seconds' => 30.0,
        'crossover_rate' => 0.8,
        'mutation_rate' => 0.03,
        'elitism' => 2,
    ],
];
