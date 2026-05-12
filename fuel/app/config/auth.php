<?php

return [
    'driver' => 'OrmAuth',
    'verify_multiple_logins' => false,
    // No cambies este valor en una base con usuarios existentes: invalida los hashes actuales.
    'salt' => getenv('COREAPP_AUTH_SALT') ?: 'lkdkjioierkvueb89734b45378234n5b6c7a8d9f0',
    'iterations' => 10000,
];
