<?php

return [
    'welcome' => [
        'subject' => 'Welcome to :app_name',
        'greeting' => 'Hello :name,',
        'body' => 'Thank you for joining our platform. We are excited to have you on board!',
        'action' => 'Get Started',
    ],
    'password_reset' => [
        'subject' => 'Reset Your Password - :app_name',
        'greeting' => 'Hello :name,',
        'body' => 'You are receiving this email because we received a password reset request for your account.',
        'action' => 'Reset Password',
        'expiry' => 'This password reset link will expire in :count minutes.',
        'warning' => 'If you did not request a password reset, no further action is required.',
    ],
    'verify_email' => [
        'subject' => 'Verify Your Email Address - :app_name',
        'greeting' => 'Hello :name,',
        'body' => 'Please click the button below to verify your email address.',
        'action' => 'Verify Email Address',
    ],
];
