<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Guarantor form PDF path
    |--------------------------------------------------------------------------
    | Path relative to public/ where the BNPL guarantor form PDF is stored.
    | Place your guarantor-form.pdf in public/documents/ (or this path).
    */
    'guarantor_form_path' => env('GUARANTOR_FORM_PATH', 'documents/guarantor-form.pdf'),
];
