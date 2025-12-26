<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // DASHBOARD
        Gate::define('view-admin-dashboard', function ($user) {
            return $user->role === 'it_support';
        });
        Gate::define('view-manager-dashboard', function ($user) {
            return in_array($user->role, ['manager', 'it_support'], true);
        });
        Gate::define('view-supervisor-dashboard', function ($user) {
            return in_array($user->role, ['supervisor', 'manager', 'it_support'], true);
        });
        Gate::define('view-kasir-dashboard', function ($user) {
            return $user->role === 'kasir';
        });

        // SUPERVISOR MENU
        Gate::define('view-supervisor-monitorShift', function ($user) {
            return in_array($user->role, ['supervisor', 'manager', 'it_support'], true);
        });
        Gate::define('view-supervisor-laporanStok', function ($user) {
            return in_array($user->role, ['supervisor', 'manager', 'it_support'], true);
        });
        Gate::define('view-supervisor-shiftDetails', function ($user) {
            return in_array($user->role, ['supervisor', 'manager', 'it_support'], true);
        });

        // MANAGER MENU
        Gate::define('view-manager-userManagement', function ($user) {
            return in_array($user->role, ['manager', 'it_support'], true);
        });
        Gate::define('view-manager-cabang', function ($user) {
            return in_array($user->role, ['manager', 'it_support'], true);
        });
        Gate::define('view-manager-systemLog', function ($user) {
            return $user->role === 'it_support';
        });

        // GENERALLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLL
        Gate::define('view-stok', function ($user) {
            return in_array($user->role, ['kasir', 'supervisor', 'manager', 'it_support'], true);
        });
        Gate::define('view-produk', function ($user) {
            return in_array($user->role, ['supervisor', 'manager', 'it_support'], true);
        });
        Gate::define('view-laporan', function ($user) {
            return in_array($user->role, ['supervisor', 'manager', 'it_support'], true);
        });
        Gate::define('view-transaksi', function ($user) {
            return in_array($user->role, ['supervisor', 'manager', 'it_support'], true);
        });
        Gate::define('view-sinkronisasi', function ($user) {
            return $user->role === 'it_support';
        });
        Gate::define('view-it-support', function ($user) {
            return in_array($user->role, ['admin', 'it_support'], true);
        });

        // OPERATION
        Gate::define('create-user', function ($user) {
            return in_array($user->role, ['it_support', 'manager', 'supervisor'], true);
        });
        Gate::define('edit-user', function ($user) {
            return in_array($user->role, ['it_support', 'manager', 'supervisor'], true);
        });
        Gate::define('delete-user', function ($user) {
            return in_array($user->role, ['it_support', 'manager', 'supervisor'], true);
        });
        Gate::define('create-cabang', function ($user) {
            return $user->role === 'it_support';
        });
        Gate::define('edit-cabang', function ($user) {
            return $user->role === 'it_support';
        });
        Gate::define('delete-cabang', function ($user) {
            return $user->role === 'it_support';
        });
        Gate::define('create-stok', function ($user) {
            return in_array($user->role, ['supervisor', 'manager', 'it_support'], true);
        });
        Gate::define('create-transaksi', function ($user) {
            return in_array($user->role, ['kasir', 'supervisor', 'manager'], true);
        });
        Gate::define('delete-transaksi', function ($user) {
            return in_array($user->role, ['manager', 'it_support'], true);
        });
    }
}
