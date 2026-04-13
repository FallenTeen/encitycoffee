<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditDiscountActivity
{
    public function handle(Request $request, Closure $next, string $subjectType = null)
    {
        $response = $next($request);

        $user = Auth::user();
        if (! $user) {
            return $response;
        }

        $method = strtoupper($request->getMethod());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $response;
        }

        if (is_object($response) && method_exists($response, 'getStatusCode')) {
            $status = (int) $response->getStatusCode();
            if ($status >= 400) {
                return $response;
            }
        }

        $subjectId = null;
        foreach (['transaksi', 'openBill', 'open_bill', 'open-bill'] as $key) {
            $param = $request->route($key);
            if (is_object($param) && isset($param->id)) {
                $subjectId = (int) $param->id;
                break;
            }
            if (is_numeric($param)) {
                $subjectId = (int) $param;
                break;
            }
        }

        $payload = $this->redactPayload($request->all());

        try {
            DB::table('audit_logs')->insert([
                'actor_user_id' => (int) $user->id,
                'method' => $method,
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'payload' => empty($payload) ? null : json_encode($payload),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }

        return $response;
    }

    private function redactPayload($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $key = is_string($k) ? strtolower($k) : '';
                if ($key !== '' && (str_contains($key, 'password') || str_contains($key, 'two_factor') || str_contains($key, 'token'))) {
                    $out[$k] = '[REDACTED]';
                    continue;
                }
                $out[$k] = $this->redactPayload($v);
            }
            return $out;
        }
        return $value;
    }
}

