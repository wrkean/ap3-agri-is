<?php
// tests/Integration/AuthTest.php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase {
    protected function setUp(): void {
        // Reset session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    // The users table is only readable with the server-only service key.
    // When it is not configured in .env, logins fail closed by design and
    // these tests cannot exercise the happy path — skip them loudly.
    private function skipIfNoServiceKey(): void {
        $key = $_ENV['SUPABASE_SERVICE_KEY'] ?? getenv('SUPABASE_SERVICE_KEY');
        if (empty($key)) {
            $this->markTestSkipped('SUPABASE_SERVICE_KEY is not set in .env; live users lookup unavailable (login fails closed).');
        }
    }

    public function testLoginWithValidAdminCredentials(): void {
        $this->skipIfNoServiceKey();
        $result = login('admin@example.com', 'admin123');
        $this->assertTrue($result);
        $this->assertTrue(isLoggedIn());
        $this->assertEquals('administrator', $_SESSION['user_role']);
        $this->assertEquals('admin@example.com', $_SESSION['user_email']);
    }

    public function testLoginWithValidStaffCredentials(): void {
        $this->skipIfNoServiceKey();
        $result = login('staff@example.com', 'staff123');
        $this->assertTrue($result);
        $this->assertTrue(isLoggedIn());
        $this->assertEquals('staff', $_SESSION['user_role']);
    }

    public function testLoginWithInvalidPassword(): void {
        $result = login('admin@example.com', 'wrongpassword');
        $this->assertFalse($result);
        $this->assertFalse(isLoggedIn());
    }

    public function testLoginWithInvalidEmail(): void {
        $result = login('nonexistent@example.com', 'password');
        $this->assertFalse($result);
        $this->assertFalse(isLoggedIn());
    }

    public function testLoginWithEmptyCredentials(): void {
        $result = login('', '');
        $this->assertFalse($result);
    }

    public function testIsAdminForAdministrator(): void {
        $this->skipIfNoServiceKey();
        login('admin@example.com', 'admin123');
        $this->assertTrue(isAdmin());
    }

    public function testIsAdminForStaff(): void {
        $this->skipIfNoServiceKey();
        login('staff@example.com', 'staff123');
        $this->assertFalse(isAdmin());
    }

    public function testIsAdminWhenNotLoggedIn(): void {
        $this->assertFalse(isAdmin());
    }

    public function testCurrentUserWhenLoggedIn(): void {
        $this->skipIfNoServiceKey();
        login('admin@example.com', 'admin123');
        $user = currentUser();
        $this->assertNotNull($user);
        $this->assertEquals('System Administrator', $user['name']);
        $this->assertEquals('administrator', $user['role']);
    }

    public function testCurrentUserWhenNotLoggedIn(): void {
        $this->assertNull(currentUser());
    }

    public function testLoginFailsClosedWhenUsersLookupFails(): void {
        // Point Supabase at an unreachable host so the users lookup fails;
        // login must fail closed instead of falling back to demo accounts.
        $origUrl = $_ENV['SUPABASE_URL'] ?? getenv('SUPABASE_URL');
        $_ENV['SUPABASE_URL'] = 'http://127.0.0.1:1';
        putenv('SUPABASE_URL=http://127.0.0.1:1');
        try {
            $result = login('admin@example.com', 'admin123');
            $this->assertFalse($result);
            $this->assertFalse(isLoggedIn());
        } finally {
            if ($origUrl !== false && $origUrl !== null) {
                $_ENV['SUPABASE_URL'] = $origUrl;
                putenv("SUPABASE_URL=$origUrl");
            } else {
                unset($_ENV['SUPABASE_URL']);
                putenv('SUPABASE_URL');
            }
        }
    }

    public function testLoginWithUnknownRoleIsRejected(): void {
        // Insert a temporary user with a role that is neither 'administrator'
        // nor 'staff' and confirm login rejects it. Once the live DB has the
        // users_role_check constraint this insert may fail; that is fine —
        // the database itself then guarantees the rejection.
        require_once __DIR__ . '/../../includes/Database.php';
        $db = new Database(true); // service key
        $email = 'test-unknown-role-' . bin2hex(random_bytes(8)) . '@example.com';
        $inserted = $db->insert('users', [
            'email' => $email,
            'password_hash' => password_hash('secret123', PASSWORD_DEFAULT),
            'role' => 'moderator',
            'full_name' => 'Test Unknown Role',
        ]);
        if ($inserted === null) {
            $this->markTestSkipped('Could not insert a test user with an unknown role (constraint enforced or service key missing).');
            return;
        }
        $userId = $inserted['user_id'] ?? ($inserted['id'] ?? null);
        try {
            $result = login($email, 'secret123');
            $this->assertFalse($result);
            $this->assertFalse(isLoggedIn());
        } finally {
            if ($userId !== null) {
                $db->delete('users', ['user_id' => 'eq.' . rawurlencode((string) $userId)]);
            }
        }
    }

    public function testLogout(): void {
        $this->skipIfNoServiceKey();
        login('admin@example.com', 'admin123');
        $this->assertTrue(isLoggedIn());
        logout();
        $this->assertFalse(isLoggedIn());
    }
}
