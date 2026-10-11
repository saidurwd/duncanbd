<?php

return [
    // Retention periods enforced daily by `privacy:prune` (confirm with Legal / Company Secretary).
    'applications_months_after_close' => (int) env('RETAIN_APPLICATIONS_MONTHS', 12),
    'enquiries_months' => (int) env('RETAIN_ENQUIRIES_MONTHS', 24),
    'pending_subscriber_days' => (int) env('RETAIN_PENDING_SUBSCRIBER_DAYS', 7),
];
