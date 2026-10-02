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
        'mail' => [
            'subject' => 'Reset your password',
            'instruction' => 'Click the button below to choose a new password for your account.',
            'action' => 'Reset password',
            'expiration' => 'This link expires in :count minutes.',
            'ignore' => 'If you did not ask to reset your password, you can ignore this email.',
        ],
        'sent' => 'If an account uses this address, it will receive a link to reset its password.',
        'reset' => 'Your password is reset. Sign in with the new one.',
    ],
    'passkeys' => [
        'added' => 'Your passkey is added.',
        'invalid' => 'Your passkey could not be verified. Try again.',
    ],
];
