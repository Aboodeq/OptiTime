<?php

declare(strict_types=1);

/**
 * Generates postman/OptiTime.postman_collection.json — run: php scripts/build_postman_collection.php
 */
$base = '{{base_url}}/api';

$bearer = [
    'type' => 'bearer',
    'bearer' => [['key' => 'token', 'value' => '{{token}}', 'type' => 'string']],
];

$req = function (string $name, string $method, string $path, array $options = []) {
    $urlPath = array_values(array_filter(explode('/', str_replace('{{base_url}}/api/', '', $path))));
    $item = [
        'name' => $name,
        'request' => [
            'method' => $method,
            'header' => $options['headers'] ?? [['key' => 'Accept', 'value' => 'application/json']],
            'url' => [
                'raw' => $path,
                'host' => ['{{base_url}}'],
                'path' => array_merge(['api'], $urlPath),
            ],
        ],
    ];
    if (! empty($options['query'])) {
        $item['request']['url']['query'] = $options['query'];
    }
    if (! empty($options['body'])) {
        $item['request']['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($options['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'options' => ['raw' => ['language' => 'json']],
        ];
        $item['request']['header'][] = ['key' => 'Content-Type', 'value' => 'application/json'];
    }
    if (! empty($options['description'])) {
        $item['request']['description'] = $options['description'];
    }

    return $item;
};

$items = [];

$items[] = [
    'name' => 'Public',
    'item' => [
        $req('Login', 'POST', "$base/auth/login", [
            'body' => [
                'email' => 'admin@example.com',
                'password' => 'password',
            ],
            'description' => 'Returns token. Copy value into collection variable `token`.',
        ]),
        $req('Generate schedule (local/testing: no auth; prod: Bearer + schedule.generate)', 'POST', "$base/schedule/generate", [
            'body' => [
                'algorithm' => 'backtracking',
                'semester_id' => '{{semester_uuid}}',
                'settings_id' => '{{schedule_settings_uuid}}',
                'schedule_settings' => [
                    'day_start' => '08:00',
                    'day_end' => '18:00',
                    'slot_minutes' => 50,
                    'gap_minutes' => 10,
                    'max_daily_lectures' => 6,
                    'capacity_threshold' => 80,
                    'study_days' => [
                        'sunday' => true,
                        'monday' => true,
                        'tuesday' => true,
                        'wednesday' => true,
                        'thursday' => true,
                    ],
                    'break_times' => [['start' => '12:00', 'end' => '13:00']],
                    'constraints' => [],
                    'room_constraints' => [],
                    'load_ranges' => [],
                ],
                'baseDraft' => [
                    'sessions' => [
                        [
                            'course_offering_id' => '{{course_offering_uuid}}',
                            'section_instructor_id' => '{{section_instructor_uuid}}',
                            'course_id' => '{{course_uuid}}',
                            'course_section_id' => '{{section_uuid}}',
                            'instructor_id' => '{{instructor_uuid}}',
                            'enrollment' => 30,
                            'is_lab' => false,
                            'day' => 'sun',
                            'start' => '08:00',
                            'end' => '08:50',
                            'room_id' => '{{room_uuid}}',
                        ],
                    ],
                ],
                'rooms_override' => [
                    ['id' => '{{room_uuid}}', 'capacity' => 40, 'is_lab' => false],
                ],
                'seed' => 42,
                'instructor_availabilities' => [
                    [
                        'instructor_id' => '{{instructor_uuid}}',
                        'day_of_week' => 'sunday',
                        'start' => '08:00',
                        'end' => '16:00',
                    ],
                ],
            ],
        ]),
    ],
];

$adminEntityBodies = [
    'faculties' => [
        'store' => [
            'code' => 'ENG',
            'name_ar' => 'هندسة',
            'name_en' => 'Engineering',
            'graduation_hours' => 160,
            'studying_level' => 5,
            'color' => '#3366cc',
            'icon_url' => 'https://example.com/icon.png',
            'is_active' => true,
        ],
    ],
    'departments' => [
        'store' => [
            'faculty_id' => '{{faculty_uuid}}',
            'code' => 'CS',
            'name_ar' => 'علوم حاسوب',
            'name_en' => 'Computer Science',
            'icon_url' => null,
            'is_active' => true,
        ],
    ],
    'specializations' => [
        'store' => [
            'code' => 'AI',
            'name_ar' => 'ذكاء اصطناعي',
            'name_en' => 'Artificial Intelligence',
            'is_active' => true,
        ],
    ],
    'semesters' => [
        'store' => [
            'code' => 'S1-2026',
            'name' => 'Spring 2026',
            'academic_year' => '2025-2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ],
    ],
    'rooms' => [
        'store' => [
            'name_ar' => 'قاعة 101',
            'name_en' => 'Room 101',
            'type' => 'class',
            'capacity' => 40,
            'location_ar' => 'المبنى أ',
            'location_en' => 'Building A',
            'status' => 'available',
            'notes_ar' => null,
            'notes_en' => null,
        ],
    ],
    'resources' => [
        'store' => [
            'name_ar' => 'بروجكتور',
            'name_en' => 'Projector',
            'type' => 'av',
            'quantity' => 10,
            'location_ar' => null,
            'location_en' => null,
            'status' => 'available',
            'notes_ar' => null,
            'notes_en' => null,
        ],
    ],
    'courses' => [
        'store' => [
            'department_id' => '{{department_uuid}}',
            'code' => 'CS101',
            'name_ar' => 'مقدمة في الحاسوب',
            'name_en' => 'Intro to CS',
            'required_hours' => 3,
            'room_consumed_hours' => 2,
            'lab_consumed_hours' => 1,
            'has_lab_component' => true,
        ],
    ],
    'sections' => [
        'store' => [
            'course_id' => '{{course_uuid}}',
            'section_name' => 'A',
            'section_type' => 'room',
            'capacity' => 35,
        ],
    ],
    'schedule-settings' => [
        'store' => [
            'day_start' => '08:00',
            'day_end' => '18:00',
            'slot_minutes' => 50,
            'gap_minutes' => 10,
            'max_daily_lectures' => 6,
            'capacity_threshold' => 80,
        ],
    ],
];

$adminResources = array_keys($adminEntityBodies);

$adminItems = [];
foreach ($adminResources as $res) {
    $pathSeg = $res;
    $storeBody = $adminEntityBodies[$res]['store'];
    $updateBody = $storeBody;

    $adminItems[] = $req("List {$res}", 'GET', "$base/admin/{$pathSeg}");
    $adminItems[] = $req("Create {$res}", 'POST', "$base/admin/{$pathSeg}", ['body' => $storeBody]);
    $adminItems[] = $req("Show {$res}", 'GET', "$base/admin/{$pathSeg}/{{entity_id}}");
    $adminItems[] = $req("Update {$res}", 'PUT', "$base/admin/{$pathSeg}/{{entity_id}}", ['body' => $updateBody]);
    $adminItems[] = $req("Delete {$res}", 'DELETE', "$base/admin/{$pathSeg}/{{entity_id}}");
}

$adminFolder = [
    'name' => 'Admin',
    'auth' => $bearer,
    'item' => array_merge([
        $req('List users', 'GET', "$base/admin/users"),
        $req('Create user', 'POST', "$base/admin/users", [
            'body' => [
                'full_name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password12',
                'role_id' => '{{role_uuid}}',
                'department_id' => '{{department_uuid}}',
                'is_active' => true,
            ],
        ]),
        $req('Show user', 'GET', "$base/admin/users/{{user_uuid}}"),
        $req('Update user', 'PUT', "$base/admin/users/{{user_uuid}}", [
            'body' => [
                'full_name' => 'Updated Name',
                'email' => 'updated@example.com',
                'password' => 'newpass123',
                'role_id' => '{{role_uuid}}',
                'department_id' => '{{department_uuid}}',
                'is_active' => true,
            ],
        ]),
        $req('Delete user', 'DELETE', "$base/admin/users/{{user_uuid}}"),
        $req('List roles', 'GET', "$base/admin/roles"),
        $req('Create role', 'POST', "$base/admin/roles", [
            'body' => [
                'code' => 'custom_role',
                'name_ar' => 'دور مخصص',
                'name_en' => 'Custom role',
                'sidebar_color' => '#ff00aa',
                'permission_ids' => ['{{permission_uuid}}'],
            ],
        ]),
        $req('Show role', 'GET', "$base/admin/roles/{{role_uuid}}"),
        $req('Update role', 'PUT', "$base/admin/roles/{{role_uuid}}", [
            'body' => [
                'code' => 'custom_role',
                'name_ar' => 'دور مخصص',
                'name_en' => 'Custom role',
                'sidebar_color' => '#00aa00',
                'is_active' => true,
                'permission_ids' => ['{{permission_uuid}}'],
            ],
        ]),
        $req('Delete role', 'DELETE', "$base/admin/roles/{{role_uuid}}"),
        $req('List permissions', 'GET', "$base/admin/permissions"),
        $req('List audit logs', 'GET', "$base/admin/audit-logs", [
            'query' => [
                ['key' => 'per_page', 'value' => '30', 'description' => 'Pagination page size'],
            ],
        ]),
    ], $adminItems),
];

$coordEntityBodies = [
    'resources' => $adminEntityBodies['resources']['store'],
    'rooms' => $adminEntityBodies['rooms']['store'],
    'courses' => [
        'department_id' => '{{department_uuid}}',
        'code' => 'CS102',
        'name_ar' => 'برمجة',
        'name_en' => 'Programming',
        'required_hours' => 3,
        'has_lab_component' => false,
    ],
    'sections' => $adminEntityBodies['sections']['store'],
];

$coordItems = [];
foreach (['resources', 'rooms', 'courses', 'sections'] as $res) {
    $coordItems[] = $req("List coordinator {$res}", 'GET', "$base/coordinator/{$res}", $res === 'sections' ? [
        'query' => [
            ['key' => 'semester_id', 'value' => '{{semester_uuid}}', 'description' => 'Optional: filter sections by courses offered in semester'],
        ],
    ] : []);
    $coordItems[] = $req("Create coordinator {$res}", 'POST', "$base/coordinator/{$res}", ['body' => $coordEntityBodies[$res]]);
    $coordItems[] = $req("Show coordinator {$res}", 'GET', "$base/coordinator/{$res}/{{entity_id}}");
    $coordItems[] = $req("Update coordinator {$res}", 'PUT', "$base/coordinator/{$res}/{{entity_id}}", ['body' => $coordEntityBodies[$res]]);
    $coordItems[] = $req("Delete coordinator {$res}", 'DELETE', "$base/coordinator/{$res}/{{entity_id}}");
}

$coordinatorFolder = [
    'name' => 'Coordinator',
    'auth' => $bearer,
    'item' => array_merge([
        $req('Lookups — buildings', 'GET', "$base/coordinator/lookups/buildings"),
        $req('Lookups — room types', 'GET', "$base/coordinator/lookups/room-types"),
        $req('Lookups — course types', 'GET', "$base/coordinator/lookups/course-types"),
    ], $coordItems, [
        $req('Sync section instructors', 'POST', "$base/coordinator/sections/{{section_uuid}}/instructors", [
            'body' => [
                'instructor_ids' => ['{{instructor_uuid}}'],
            ],
        ]),
        $req('Sync course offering (course-semester)', 'POST', "$base/coordinator/course-semester", [
            'body' => [
                'course_id' => '{{course_uuid}}',
                'semester_id' => '{{semester_uuid}}',
                'status' => 'active',
            ],
        ]),
        $req('List lecture requests', 'GET', "$base/coordinator/lecture-requests"),
        $req('Update lecture request', 'PUT', "$base/coordinator/lecture-requests/{{lecture_request_uuid}}", [
            'body' => [
                'status' => 'approved',
                'review_note' => 'OK',
            ],
        ]),
        $req('List schedule plans', 'GET', "$base/coordinator/schedules", [
            'query' => [
                ['key' => 'semester_id', 'value' => '{{semester_uuid}}', 'description' => 'Optional filter'],
            ],
        ]),
        $req('Create schedule plan (draft)', 'POST', "$base/coordinator/schedules", [
            'body' => [
                'semester_id' => '{{semester_uuid}}',
                'status' => 'draft',
                'notes' => 'Draft plan',
            ],
        ]),
        $req('Show schedule plan + sessions', 'GET', "$base/coordinator/schedules/{{schedule_plan_uuid}}"),
        $req('Update schedule plan', 'PUT', "$base/coordinator/schedules/{{schedule_plan_uuid}}", [
            'body' => [
                'status' => 'draft',
                'notes' => 'Updated notes',
            ],
        ]),
        $req('Delete schedule plan', 'DELETE', "$base/coordinator/schedules/{{schedule_plan_uuid}}"),
        $req('Publish from generation', 'POST', "$base/coordinator/schedules/publish-from-generation", [
            'body' => [
                'algorithm' => 'backtracking',
                'semester_id' => '{{semester_uuid}}',
                'settings_id' => '{{schedule_settings_uuid}}',
                'schedule_settings' => null,
                'baseDraft' => null,
                'notes' => 'Published via API',
                'seed' => 1,
                'instructor_availabilities' => [
                    [
                        'instructor_id' => '{{instructor_uuid}}',
                        'day_of_week' => 'monday',
                        'start' => '08:00',
                        'end' => '14:00',
                    ],
                ],
            ],
        ]),
    ]),
];

$authItems = [
    $req('Logout', 'POST', "$base/auth/logout"),
    $req('Change password', 'POST', "$base/auth/change-password", [
        'body' => [
            'current_password' => 'oldpassword',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ],
    ]),
    $req('Current user (Sanctum)', 'GET', "$base/user"),
    $req('Get profile', 'GET', "$base/profile"),
    $req('Update profile', 'PUT', "$base/profile", [
        'body' => [
            'full_name' => 'My Name',
            'email' => 'me@example.com',
            'avatar_url' => 'https://example.com/a.png',
        ],
    ]),
    $req('Get profile settings', 'GET', "$base/profile/settings"),
    $req('Update profile settings', 'PUT', "$base/profile/settings", [
        'body' => [
            'email_schedule_updates' => true,
            'email_reminders' => true,
            'push_announcements' => false,
            'push_system_alerts' => true,
            'weekly_digest' => false,
        ],
    ]),
    $req('List notifications', 'GET', "$base/notifications"),
    $req('Mark notification read', 'POST', "$base/notifications/{{notification_uuid}}/read"),
    $adminFolder,
    $coordinatorFolder,
    [
        'name' => 'Instructor',
        'auth' => $bearer,
        'item' => [
            $req('List availabilities', 'GET', "$base/instructor/availabilities"),
            $req('Create availability cell', 'POST', "$base/instructor/availabilities", [
                'body' => [
                    'day_of_week' => 'sunday',
                    'start_time' => '08:00',
                    'end_time' => '12:00',
                    'status' => 'preferred',
                ],
            ]),
            $req('Update availability cell', 'PUT', "$base/instructor/availabilities/{{availability_uuid}}", [
                'body' => [
                    'day_of_week' => 'monday',
                    'start_time' => '09:00',
                    'end_time' => '13:00',
                    'status' => 'blocked',
                ],
            ]),
            $req('Delete availability cell', 'DELETE', "$base/instructor/availabilities/{{availability_uuid}}"),
            $req('Weekly schedule', 'GET', "$base/instructor/weekly-schedule", [
                'query' => [['key' => 'semester_id', 'value' => '{{semester_uuid}}']],
            ]),
            $req('Weekly schedule PDF', 'GET', "$base/instructor/weekly-schedule/pdf", [
                'query' => [['key' => 'semester_id', 'value' => '{{semester_uuid}}']],
            ]),
            $req('List my lecture requests', 'GET', "$base/instructor/lecture-requests"),
            $req('Create lecture request', 'POST', "$base/instructor/lecture-requests", [
                'body' => [
                    'schedule_session_id' => '{{schedule_session_uuid}}',
                    'request_type' => 'reschedule',
                    'requested_date' => '2026-05-01',
                    'note' => 'Need different slot',
                ],
            ]),
        ],
    ],
    [
        'name' => 'Student',
        'auth' => $bearer,
        'item' => [
            $req('Weekly schedule', 'GET', "$base/student/weekly-schedule", [
                'query' => [['key' => 'semester_id', 'value' => '{{semester_uuid}}']],
            ]),
            $req('Weekly schedule PDF', 'GET', "$base/student/weekly-schedule/pdf", [
                'query' => [['key' => 'semester_id', 'value' => '{{semester_uuid}}']],
            ]),
            $req('Grades by semester', 'GET', "$base/student/grades", [
                'query' => [['key' => 'semester_id', 'value' => '{{semester_uuid}}']],
            ]),
            $req('Grades PDF', 'GET', "$base/student/grades/pdf", [
                'query' => [['key' => 'semester_id', 'value' => '{{semester_uuid}}']],
            ]),
        ],
    ],
    [
        'name' => 'Management reports',
        'auth' => $bearer,
        'item' => [
            $req('Classroom occupancy', 'GET', "$base/management/reports/classroom-occupancy", [
                'query' => [['key' => 'semester_id', 'value' => '{{semester_uuid}}']],
            ]),
            $req('Lab utilization', 'GET', "$base/management/reports/lab-utilization", [
                'query' => [['key' => 'semester_id', 'value' => '{{semester_uuid}}']],
            ]),
            $req('Study hours distribution', 'GET', "$base/management/reports/study-hours-distribution", [
                'query' => [['key' => 'semester_id', 'value' => '{{semester_uuid}}']],
            ]),
            $req('Peak periods', 'GET', "$base/management/reports/peak-periods", [
                'query' => [['key' => 'semester_id', 'value' => '{{semester_uuid}}']],
            ]),
            $req('Resource utilization', 'GET', "$base/management/reports/resource-utilization", [
                'query' => [
                    ['key' => 'semester_id', 'value' => '{{semester_uuid}}', 'description' => 'Optional'],
                ],
            ]),
            $req('Compliance restrictions', 'GET', "$base/management/reports/compliance-restrictions", [
                'query' => [['key' => 'semester_id', 'value' => '{{semester_uuid}}']],
            ]),
        ],
    ],
    [
        'name' => 'Exams',
        'auth' => $bearer,
        'item' => [
            $req('List exam sessions (sections + status)', 'GET', "$base/exams/sessions", [
                'query' => [['key' => 'semester_id', 'value' => '{{semester_uuid}}']],
            ]),
            $req('Mark section grades done', 'POST', "$base/exams/sessions/mark-done", [
                'body' => [
                    'section_id' => '{{section_uuid}}',
                    'semester_id' => '{{semester_uuid}}',
                ],
            ]),
            $req('Upsert session grade row', 'POST', "$base/exams/grades", [
                'body' => [
                    'student_id' => '{{student_user_uuid}}',
                    'schedule_session_id' => '{{schedule_session_uuid}}',
                    'oral' => 5,
                    'lab' => 10,
                    'midterm' => 25,
                    'final' => 40,
                    'total' => 80,
                    'letter_grade' => 'B',
                ],
            ]),
            $req('Session grades PDF', 'GET', "$base/exams/grades/session-pdf", [
                'query' => [['key' => 'schedule_session_id', 'value' => '{{schedule_session_uuid}}']],
            ]),
        ],
    ],
];

$collection = [
    'info' => [
        '_postman_id' => 'optitime-api-'.bin2hex(random_bytes(8)),
        'name' => 'OptiTime API (complete)',
        'description' => "Laravel API under `/api`. Set `base_url` (e.g. http://localhost:8000) and `token` from Login.\n\nReplace `{{..._uuid}}` variables with real UUIDs from your DB or seed.\n\n`POST /schedule/generate` is open in local/testing; in production requires Bearer + `schedule.generate` permission.",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'variable' => [
        ['key' => 'base_url', 'value' => 'http://localhost:8000'],
        ['key' => 'token', 'value' => ''],
        ['key' => 'semester_uuid', 'value' => ''],
        ['key' => 'role_uuid', 'value' => ''],
        ['key' => 'permission_uuid', 'value' => ''],
        ['key' => 'user_uuid', 'value' => ''],
        ['key' => 'faculty_uuid', 'value' => ''],
        ['key' => 'department_uuid', 'value' => ''],
        ['key' => 'course_uuid', 'value' => ''],
        ['key' => 'section_uuid', 'value' => ''],
        ['key' => 'room_uuid', 'value' => ''],
        ['key' => 'entity_id', 'value' => ''],
        ['key' => 'schedule_settings_uuid', 'value' => ''],
        ['key' => 'course_offering_uuid', 'value' => ''],
        ['key' => 'section_instructor_uuid', 'value' => ''],
        ['key' => 'instructor_uuid', 'value' => ''],
        ['key' => 'schedule_plan_uuid', 'value' => ''],
        ['key' => 'schedule_session_uuid', 'value' => ''],
        ['key' => 'student_user_uuid', 'value' => ''],
        ['key' => 'notification_uuid', 'value' => ''],
        ['key' => 'availability_uuid', 'value' => ''],
        ['key' => 'lecture_request_uuid', 'value' => ''],
    ],
    'item' => array_merge([
        [
            'name' => 'Public',
            'item' => $items[0]['item'],
        ],
        [
            'name' => 'Authenticated',
            'auth' => $bearer,
            'item' => $authItems,
        ],
    ]),
];

$outDir = dirname(__DIR__).'/postman';
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}
$outFile = $outDir.'/OptiTime.postman_collection.json';
file_put_contents($outFile, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

echo "Wrote {$outFile}\n";
