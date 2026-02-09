<?php

return [
    /**
     * Which web role should be allowed to APPROVE/DISAPPROVE employee requests
     * (leave + ATRO) in ESS pages.
     *
     * Example:
     * ESS_APPROVER_ROLE=manager
     * ESS_APPROVER_ROLE=admin
     * ESS_APPROVER_ROLE=admins
     */
    'approver_role' => env('ESS_APPROVER_ROLE', 'admins'),

    /**
     * Always allow superadmin to approve.
     */
    'allow_superadmin' => env('ESS_APPROVER_ALLOW_SUPERADMIN', true),
];

