<?php

declare(strict_types=1);

return [
    'verification' => [
        'mail' => [
            'subject' => 'Verify your email address',
            'instruction' => 'Click the button below to verify your email address.',
            'action' => 'Verify email address',
            'expiration' => 'This link expires in :count minutes.',
            'ignore' => 'If you did not create an account, you can ignore this email.',
        ],
        'sent' => 'A new verification link was sent to your email address.',
        'verified' => 'Your email address is verified.',
    ],
    'password' => [
        'reset' => 'Your password is reset. Sign in with the new one.',
    ],
];
