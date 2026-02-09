<?php

return [
    /**
     * Comma-separated list of allowed recipient email domains for notification emails.
     *
     * Example:
     * NOTIFICATION_EMAIL_ALLOWED_DOMAINS=novulutions.com
     * NOTIFICATION_EMAIL_ALLOWED_DOMAINS=novulutions.com,example.com
     *
     * If empty, no domain restriction is applied.
     */
    'email_allowed_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('NOTIFICATION_EMAIL_ALLOWED_DOMAINS', ''))))),
];

