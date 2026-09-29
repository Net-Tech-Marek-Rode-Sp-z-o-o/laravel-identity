<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NetCode\Identity\Application\Ports\CurrentUser;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureEmailIsVerified
{
    public function __construct(
        private CurrentUser $currentUser,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->currentUser->userOrNull();

        if ($user !== null && ! $user->emailVerified) {
            return new JsonResponse(
                data: ['message' => 'Your e-mail address is not verified.'],
                status: Response::HTTP_FORBIDDEN,
            );
        }

        return $next($request);
    }
}
