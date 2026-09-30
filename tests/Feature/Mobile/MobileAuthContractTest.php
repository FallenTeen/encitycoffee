<?php

namespace Tests\Feature\Mobile;

use App\Models\Cabang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Locks the authentication / session contract between Laravel and the mobile
 * POS client.
 *
 * The invariant under test: a session may only be destroyed when the backend
 * explicitly states the credential is not valid (HTTP 401). Database failures,
 * timeouts, validation errors and unexpected exceptions must never be
 * reported as 401, and raw exception detail must never reach the client.
 */
class MobileAuthContractTest extends TestCase
{
    use RefreshDatabase;

    protected User $kasir;

    protected Cabang $cabang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cabang = Cabang::create([
            'kode' => 'CBG-AUTH',
            'nama' => 'Cabang Auth',
            'aktif' => true,
        ]);

        $this->kasir = User::create([
            'name' => 'Kasir Kontrak',
            'email' => 'kasir.kontrak@test.com',
            'password' => Hash::make('password123'),
            'role' => 'kasir',
            'aktif' => true,
        ]);

        $this->kasir->cabang()->attach($this->cabang->id);
    }

    protected function issueToken(): string
    {
        return $this->kasir->createToken('pos-token')->plainTextToken;
    }

    protected function authHeaders(string $token): array
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ];
    }

    // -----------------------------------------------------------------
    // 1. Invalid token => 401 (and only that)
    // -----------------------------------------------------------------

    public function test_invalid_token_on_mobile_endpoints_returns_401()
    {
        $endpoints = [
            ['get', '/api/pos/auth/me'],
            ['get', '/api/pos/cabang'],
            ['get', '/api/pos/shift/aktif'],
            ['get', '/api/pos/produk/mobile'],
            ['get', '/api/pos/laporan/cabang/'.$this->cabang->id.'/harian'],
            ['get', '/api/viewer/shift/aktif'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $response = $this->withHeaders($this->authHeaders('totally-invalid-token'))
                ->json($method, $uri);

            $response->assertStatus(401, "Expected 401 for $method $uri");
        }
    }

    public function test_missing_token_on_mobile_endpoints_returns_401()
    {
        $this->getJson('/api/pos/auth/me')->assertStatus(401);
        $this->getJson('/api/pos/cabang')->assertStatus(401);
        $this->getJson('/api/pos/shift/aktif')->assertStatus(401);
    }

    public function test_expired_token_returns_401_and_null_token_returns_401()
    {
        $token = $this->issueToken();

        // Expiry is enforced by auth:sanctum itself (Laravel\Sanctum\Guard
        // rejects an expires_at in the past), so no extra token middleware is
        // required on the session routes.
        $this->kasir->tokens()->update(['expires_at' => now()->subMinute()]);

        $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/pos/auth/me')
            ->assertStatus(401);

        // A well-formed bearer value that matches no row is also 401.
        $this->withHeaders($this->authHeaders('1|abcdefghijklmnopqrstuvwxyz012345'))
            ->getJson('/api/pos/auth/me')
            ->assertStatus(401);
    }

    // -----------------------------------------------------------------
    // 2. Successful session keeps working
    // -----------------------------------------------------------------

    public function test_valid_token_reaches_the_cashier_endpoints()
    {
        $token = $this->issueToken();

        $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/pos/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $this->kasir->id);

        $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/pos/cabang')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_login_logout_me_flow_still_works()
    {
        $login = $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir.kontrak@test.com',
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonStructure(['message', 'user' => ['id', 'name', 'email', 'role'], 'token']);

        $token = $login->json('token');
        $this->assertNotEmpty($token);

        $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/pos/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $this->kasir->id);

        $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/pos/auth/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logout berhasil']);

        // After logout the same token is no longer usable.
        // AuthManager caches guard instances (and RequestGuard caches the
        // resolved user) for the lifetime of the test, while a real HTTP request
        // always builds a fresh container. Forget the guards so this models the
        // next real request rather than the stale in-memory user.
        $this->app['auth']->forgetGuards();

        $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/pos/auth/me')
            ->assertStatus(401);
    }

    // -----------------------------------------------------------------
    // 3. Unexpected failures are 500, never 401, and never leak detail
    // -----------------------------------------------------------------

    public function test_unexpected_exception_is_500_and_never_401()
    {
        Route::middleware(['auth:sanctum'])->get('/api/pos/__boom', function () {
            throw new RuntimeException('SQLSTATE[42S02]: table "secret_table" not found at /var/www/app/secret.php');
        });

        $token = $this->issueToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/pos/__boom');

        // 500, not 401: the client must keep the cashier session.
        $response->assertStatus(500);

        $body = $response->getContent();
        $this->assertStringNotContainsString('secret_table', $body);
        $this->assertStringNotContainsString('secret.php', $body);
        $this->assertStringNotContainsString('SQLSTATE', $body);

        // A generic, actionable message plus a correlation id for the log.
        $response->assertJsonStructure(['message', 'error', 'error_id']);
        $this->assertNotEmpty($response->json('error_id'));
    }

    public function test_database_failure_is_not_reported_as_401()
    {
        // Force the token lookup itself to fail, which is the most dangerous
        // place for a database problem to be misread as an auth failure.
        $token = $this->issueToken();

        DB::listen(function () {
            throw new RuntimeException('database is down');
        });

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/pos/cabang');

        $this->assertNotSame(401, $response->status());
        $response->assertStatus(500);
        $this->assertStringNotContainsString('database is down', $response->getContent());
    }

    public function test_server_error_without_json_accept_header_still_returns_json()
    {
        Route::middleware(['auth:sanctum'])->get('/api/pos/__boom-no-header', function () {
            throw new RuntimeException('internal detail that must not leak');
        });

        $token = $this->issueToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get('/api/pos/__boom-no-header');

        $response->assertStatus(500);
        $response->assertHeader('content-type', 'application/json');
        $this->assertStringNotContainsString('internal detail', $response->getContent());
    }

    // -----------------------------------------------------------------
    // 4. Login status semantics
    // -----------------------------------------------------------------

    public function test_login_contract_statuses()
    {
        // 422 validation
        $this->postJson('/api/pos/auth/login', ['password' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // 401 invalid credentials
        $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir.kontrak@test.com',
            'password' => 'wrong-password',
        ])->assertStatus(401);

        // 403 disabled account
        $this->kasir->update(['aktif' => false]);
        $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir.kontrak@test.com',
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_login_lookup_failure_is_500_not_401()
    {
        Route::post('/api/pos/auth/login', function () {
            throw new RuntimeException('connection to users table failed');
        })->middleware('api');

        $response = $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir.kontrak@test.com',
            'password' => 'password123',
        ]);

        // A failing user lookup is a server problem: the mobile client must not
        // interpret it as "wrong password".
        $response->assertStatus(500);
        $this->assertStringNotContainsString('connection to users table', $response->getContent());
    }

    public function test_unexpected_login_error_does_not_leak_details()
    {
        Route::post('/api/pos/auth/login', function () {
            throw new RuntimeException('PDOException: could not find driver');
        })->middleware('api');

        $response = $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir.kontrak@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(500);
        $this->assertStringNotContainsString('PDOException', $response->getContent());
        $this->assertStringNotContainsString('could not find driver', $response->getContent());
    }

    // -----------------------------------------------------------------
    // 5. Viewer endpoints must require authentication
    // -----------------------------------------------------------------

    public function test_login_returns_json_even_without_accept_header()
    {
        // A client that posts form-urlencoded data and forgets the
        // "Accept: application/json" header must still get the documented
        // status codes, never a 302 redirect to the web login page.
        $this->post('/api/pos/auth/login', [
            'email' => 'kasir.kontrak@test.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertJsonPath('error', 'Email atau password salah.')
            ->assertJsonPath('message', 'Email atau password salah.')
            ->assertJsonPath('errors.email', 'Kredensial salah');

        $this->post('/api/pos/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ])->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['email']]);
    }

    public function test_viewer_endpoints_require_authentication()
    {
        $viewerEndpoints = [
            '/api/viewer/shift/aktif',
            '/api/viewer/produk',
            '/api/viewer/stok/cabang/'.$this->cabang->id,
            '/api/viewer/laporan/cabang/'.$this->cabang->id.'/harian',
        ];

        foreach ($viewerEndpoints as $uri) {
            $this->getJson($uri)->assertStatus(401, "Expected 401 for unauthenticated $uri");
        }
    }

    public function test_viewer_endpoints_reject_disabled_account_with_403_not_401()
    {
        $token = $this->issueToken();

        // A valid token held by a disabled account is a permission problem (403),
        // never an authentication problem (401).
        $this->kasir->update(['aktif' => false]);

        $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/viewer/shift/aktif')
            ->assertStatus(403);

        $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/pos/cabang')
            ->assertStatus(403);
    }
}
