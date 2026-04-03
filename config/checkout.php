<?php

return [
    'delivery_fee' => env('CHECKOUT_DELIVERY_FEE', 2000),
    'delivery_min_working_days' => (int) env('CHECKOUT_DELIVERY_MIN_DAYS', 7),
    'delivery_max_working_days' => (int) env('CHECKOUT_DELIVERY_MAX_DAYS', 10),
    'insurance_fee' => (int) env('CHECKOUT_INSURANCE_FEE', 0),
    'installation_schedule_working_days' => (int) env('CHECKOUT_INSTALL_LEAD_DAYS', 7),
    'installation_price' => 2000,
    'installation_text' => 'Installation will be carried out by our skilled technicians. You can choose to use our installers.',
];