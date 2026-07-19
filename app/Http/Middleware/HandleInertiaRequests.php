<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        $userData = null;
        
        if ($user) {
            // Eager load the cabang relationship to avoid N+1 queries
            $user->load('cabang:id,nama,kode');
            
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar ?? null,
                'two_factor_enabled' => $user->two_factor_enabled ?? false,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'created_at' => $user->created_at?->toISOString(),
                'updated_at' => $user->updated_at?->toISOString(),
                // Include assigned branches for outlet switching
                'cabang' => in_array($user->role, ['manager', 'supervisor', 'kasir'])
                    ? $user->cabang->map(fn($c) => [
                        'id' => $c->id,
                        'nama' => $c->nama,
                        'kode' => $c->kode ?? null,
                    ])->all()
                    : [],
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'auth' => [
                'user' => $userData,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // Storage configuration for images - used by SafeImage and other image components
            'storage' => [
                // Base URL for storage files (e.g., '/storage' or custom CDN URL)
                // Can be configured via STORAGE_BASE_URL in .env
                'base_url' => env('STORAGE_BASE_URL', '/storage'),
                // Disk name for public storage
                'public_disk' => 'public',
            ],
        ];
    }
}
