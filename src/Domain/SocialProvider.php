<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain;

/** Backing values match Socialite driver names. */
enum SocialProvider: string
{
    case Google = 'google';
    case Facebook = 'facebook';
}
