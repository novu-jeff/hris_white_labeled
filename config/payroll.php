<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Night Shift Differential
    |--------------------------------------------------------------------------
    | Default percentage (0-100) for night shift differential. The actual value
    | is stored in settings (key: night_shift_differential) and editable via
    | Admin → Settings → Payroll Settings. Use Setting::getFloat('night_shift_differential', 10).
    */
    'night_shift_differential_default' => 10,
];
