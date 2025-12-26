<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use App\Models\Cabang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class MobileLoginTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $cabang;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->cabang = Cabang::create([
            'kode' => 'CBG001',
            'nama' => 'Cabang Test',
            'alamat' => 'Jl. Test No. 1',
            'telepon' => '08123456789',
            'aktif' => true
        ]);

        $this->user = User::create([
            'name' => 'Kasir Test',
            'email' => 'kasir@test.com',
            'password' => Hash::make('password123'),
            'role' => 'kasir',
            'aktif' => true
        ]);

        // Assign user to cabang
        $this->user->cabang()->attach($this->cabang->id);
    }

    /**
     * Test successful login for kasir
     */
    public function test_successful_kasir_login()
    {
        Log::info('Testing successful kasir login', ['email' => 'kasir@test.com']);

        $response = $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'user' => ['id', 'name', 'email', 'role'],
                    'token'
                ])
                ->assertJson([
                    'message' => 'Login berhasil',
                    'user' => [
                        'email' => 'kasir@test.com',
                        'role' => 'kasir'
                    ]
                ]);

        // Verify token was created
        $this->assertNotEmpty($response->json('token'));
        
        Log::info('Kasir login successful', ['user_id' => $response->json('user.id')]);
    }

    /**
     * Test login with wrong credentials
     */
    public function test_login_with_wrong_credentials()
    {
        Log::info('Testing login with wrong credentials');

        $response = $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir@test.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                ->assertJsonStructure(['errors'])
                ->assertJson([
                    'errors' => ['email' => 'Kredensial salah']
                ]);

        Log::info('Login with wrong credentials correctly rejected');
    }

    /**
     * Test login for non-kasir role (should be rejected)
     */
    public function test_non_kasir_login_rejected()
    {
        // Create manager user
        $manager = User::create([
            'name' => 'Manager Test',
            'email' => 'manager@test.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'aktif' => true
        ]);

        Log::info('Testing non-kasir login rejection', ['role' => 'manager']);

        $response = $this->postJson('/api/pos/auth/login', [
            'email' => 'manager@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'error' => 'Hanya kasir yang dapat login di aplikasi mobile'
                ]);

        Log::info('Non-kasir login correctly rejected', ['role' => 'manager']);
    }

    /**
     * Test login for inactive user
     */
    public function test_inactive_user_login_rejected()
    {
        // Create inactive kasir
        $inactiveKasir = User::create([
            'name' => 'Inactive Kasir',
            'email' => 'inactive@test.com',
            'password' => Hash::make('password123'),
            'role' => 'kasir',
            'aktif' => false
        ]);

        Log::info('Testing inactive user login rejection');

        $response = $this->postJson('/api/pos/auth/login', [
            'email' => 'inactive@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'error' => 'Akun tidak aktif'
                ]);

        Log::info('Inactive user login correctly rejected');
    }

    /**
     * Test session management after login
     */
    public function test_session_management()
    {
        Log::info('Testing session management');

        // Login
        $response = $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir@test.com',
            'password' => 'password123'
        ]);

        $token = $response->json('token');

        // Test accessing protected endpoint
        $meResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/pos/auth/me');

        $meResponse->assertStatus(200)
                  ->assertJsonStructure(['user']);

        // Test logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/pos/auth/logout');

        $logoutResponse->assertStatus(200)
                      ->assertJson([
                          'message' => 'Logout berhasil'
                      ]);

        // Verify token is deleted by trying to access protected endpoint
        $afterLogoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/pos/auth/me');

        $afterLogoutResponse->assertStatus(401);

        Log::info('Session management test completed successfully');
    }

    /**
     * Test validation for login request
     */
    public function test_login_validation()
    {
        Log::info('Testing login validation');

        // Test missing email
        $response = $this->postJson('/api/pos/auth/login', [
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

        // Test missing password
        $response = $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir@test.com'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);

        // Test invalid email format
        $response = $this->postJson('/api/pos/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

        Log::info('Login validation tests completed');
    }
}