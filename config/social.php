<?php

return [
    // Replace the platform homepages with your profile URLs in .env.
    'links' => [
        ['label' => 'Facebook', 'icon' => 'fa-facebook-f', 'url' => env('SOCIAL_FACEBOOK_URL', 'https://www.facebook.com/')],
        ['label' => 'WhatsApp', 'icon' => 'fa-whatsapp', 'url' => env('SOCIAL_WHATSAPP_URL', 'https://www.whatsapp.com/')],
        ['label' => 'Instagram', 'icon' => 'fa-instagram', 'url' => env('SOCIAL_INSTAGRAM_URL', 'https://www.instagram.com/')],
        ['label' => 'LinkedIn', 'icon' => 'fa-linkedin-in', 'url' => env('SOCIAL_LINKEDIN_URL', 'https://www.linkedin.com/')],
    ],
];
