<?php

return [
    'default_schedule_settings_path' => database_path('data/default_schedule_settings.json'),

    'backtracking' => [
        'max_backtracks' => 100_000,
        'max_seconds' => 300.0,
        'max_recursive_steps' => 500_000,
        'failed_state_cache_max' => 20_000,
        'failed_state_cache_min_depth' => 6,
        'dynamic_mcv_sample_size' => 28,
        'forward_check_sample_size' => 14,
        'hardest_unscheduled_limit' => 8,
        'progress_probe_interval' => 256,
    ],

    'genetic' => [
        'population_size' => 80,
        'max_generations' => 200,
        'max_seconds' => 10.0,
        'crossover_rate' => 0.8,
        'mutation_rate' => 0.03,
        'elitism' => 2,
    ],
];
