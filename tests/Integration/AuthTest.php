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

    public function testLoginWithValidAdminCredentials(): void {
        $result = login('admin@example.com', 'admin123');
        $this->assertTrue($result);
        $this->assertTrue(isLoggedIn());
        $this->assertEquals('administrator', $_SESSION['user_role']);
        $this->assertEquals('admin@example.com', $_SESSION['user_email']);
    }

    public function testLoginWithValidStaffCredentials(): void {
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
        login('admin@example.com', 'admin123');
        $this->assertTrue(isAdmin());
    }

    public function testIsAdminForStaff(): void {
        login('staff@example.com', 'staff123');
        $this->assertFalse(isAdmin());
    }

    public function testIsAdminWhenNotLoggedIn(): void {
        $this->assertFalse(isAdmin());
    }

    public function testCurrentUserWhenLoggedIn(): void {
        login('admin@example.com', 'admin123');
        $user = currentUser();
        $this->assertNotNull($user);
        $this->assertEquals('System Administrator', $user['name']);
        $this->assertEquals('administrator', $user['role']);
    }

    public function testCurrentUserWhenNotLoggedIn(): void {
        $this->assertNull(currentUser());
    }

    public function testLogout(): void {
        login('admin@example.com', 'admin123');
        $this->assertTrue(isLoggedIn());
        logout();
        $this->assertFalse(isLoggedIn());
    }
}
