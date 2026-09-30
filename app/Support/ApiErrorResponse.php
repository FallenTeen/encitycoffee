<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Canonical, predictable error envelope for the JSON API consumed by the
 * mobile POS client.
 *
 * Semantics that the client relies on:
 *  - 401 => "the supplied credential is not valid" and nothing else.
 *  - 403 => authenticated, but not allowed to perform the action.
 *  - 404 => the requested resource does not exist.
 *  - 409 => business conflict (e.g. shift still has open bills).
 *  - 422 => validation error.
 *  - 429 => rate limited.
 *  - 5xx => unexpected failure. Generic user-facing message only; the real
 *          detail (including the exception message) is written to the log
 *          and never serialised to the client.
 *
 * Network failures and timeouts are transport level concerns: they are never
 * represented as a 401, so the mobile client can keep the cashier session.
 */
class ApiErrorResponse
{
    public const GENERIC_SERVER_ERROR = 'Terjadi kesalahan pada server. Silakan coba lagi.';

    /**
     * Build an error response using the canonical envelope.
     *
     * The envelope intentionally exposes the human readable text under both
     * "message" and "error" so existing clients keep working regardless of
     * which key they read.
     *
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $extra
     */
    public static function make(int $status, string $message, array $errors = [], array $extra = []): JsonResponse
    {
        $payload = array_merge(
            ['message' => $message, 'error' => $message],
            $errors === [] ? [] : ['errors' => $errors],
            $extra
        );

        return response()->json($payload, $status);
    }

    /**
     * Validation keeps Laravel's native payload ("message" + per field
     * "errors") so existing clients keep showing the field level detail.
     */
    public static function validation(ValidationException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], 422);
    }

    public static function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return self::make(401, $message);
    }

    public static function forbidden(string $message = 'Akses ditolak.'): JsonResponse
    {
        return self::make(403, $message);
    }

    /**
     * Only JSON API requests are rendered with this envelope. Everything else
     * is left untouched so the web/Inertia stack keeps its own behaviour
     * (redirects, session handling, ...).
     */
    public static function handles(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    /**
     * Render a throwable for a JSON API request, or return null to let the
     * framework handle it with its default behaviour.
     */
    public static function forThrowable(Throwable $e, Request $request): ?JsonResponse
    {
        if (! self::handles($request)) {
            return null;
        }

        // Validation: 422, and the per-field "errors" payload is preserved.
        if ($e instanceof ValidationException) {
            return self::validation($e);
        }

        // 401 is reserved for an invalid credential. It must never be produced
        // by a database, network or generic server failure.
        if ($e instanceof AuthenticationException) {
            return self::unauthorized();
        }

        if ($e instanceof AuthorizationException) {
            return self::make(403, self::safeMessage($e, 'Akses ditolak.'));
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return self::make(404, self::safeMessage($e, 'Data tidak ditemukan.'));
        }

        if ($e instanceof HttpExceptionInterface) {
            return self::forHttpException($e);
        }

        return self::serverError($e, $request);
    }

    /**
     * Curated messages thrown through abort()/HttpException are safe to relay.
     * Anything on the 5xx side is not, so it degrades to a generic message.
     */
    private static function forHttpException(HttpExceptionInterface $e): JsonResponse
    {
        $status = $e->getStatusCode();

        return match (true) {
            $status === 401 => self::unauthorized(),
            $status === 403 => self::make(403, self::safeMessage($e, 'Akses ditolak.')),
            $status === 404 => self::make(404, self::safeMessage($e, 'Data tidak ditemukan.')),
            $status === 409 => self::make(409, self::safeMessage($e, 'Konflik data.')),
            $status === 429 => self::make(429, self::safeMessage($e, 'Terlalu banyak permintaan. Coba lagi nanti.')),
            $status >= 500 => self::serverError($e),
            default => self::make($status, self::safeMessage($e, 'Permintaan gagal diproses.')),
        };
    }

    /**
     * Unexpected failure. The client only ever sees a generic message plus a
     * correlation id; everything else goes to the Laravel log.
     */
    private static function serverError(Throwable $e, ?Request $request = null): JsonResponse
    {
        $errorId = (string) Str::uuid();

        Log::error('API request failed with unexpected error', [
            'error_id' => $errorId,
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'method' => $request?->getMethod(),
            'path' => $request?->path(),
            'user_id' => $request?->user()?->id,
        ]);

        return self::make(500, self::GENERIC_SERVER_ERROR, [], ['error_id' => $errorId]);
    }

    /**
     * Prefer a curated, human readable message. Never fall back to the raw
     * exception message for client facing responses.
     */
    private static function safeMessage(Throwable $e, string $fallback): string
    {
        $message = trim($e->getMessage());

        return $message === '' ? $fallback : $message;
    }
}
