<?php
declare(strict_types=1);

namespace App;

/**
 * Admin user CRUD with safeguards:
 *  • can't delete yourself
 *  • can't delete (or demote) the last remaining admin
 *  • email uniqueness enforced at validation + DB constraint
 */
final class User
{
    public const ROLES = ['admin', 'manager'];
    public const ROLE_LABELS = [
        'admin'   => 'Администратор',
        'manager' => 'Мениджър',
    ];

    public static function find(int $id): ?array
    {
        return Db::fetchOne(
            'SELECT id, email, name, role, last_login_at, created_at FROM users WHERE id = ?',
            [$id]
        );
    }

    public static function findByEmail(string $email): ?array
    {
        return Db::fetchOne(
            'SELECT id, email, name, role FROM users WHERE email = ?',
            [$email]
        );
    }

    public static function listAll(): array
    {
        return Db::fetchAll(
            'SELECT id, email, name, role, last_login_at, created_at
               FROM users ORDER BY created_at DESC'
        );
    }

    public static function adminCount(): int
    {
        return (int) (Db::fetchOne("SELECT COUNT(*) c FROM users WHERE role = 'admin'")['c'] ?? 0);
    }

    public static function create(array $data): int
    {
        Db::execute(
            'INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)',
            [
                trim((string) $data['email']),
                Auth::hash((string) $data['password']),
                trim((string) $data['name']),
                (string) $data['role'],
            ]
        );
        return Db::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $sets   = ['email = ?', 'name = ?', 'role = ?'];
        $params = [
            trim((string) $data['email']),
            trim((string) $data['name']),
            (string) $data['role'],
        ];
        $newPass = (string) ($data['password'] ?? '');
        if ($newPass !== '') {
            $sets[]   = 'password_hash = ?';
            $params[] = Auth::hash($newPass);
        }
        $params[] = $id;
        Db::execute('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    public static function delete(int $id, int $currentUserId): void
    {
        if ($id === $currentUserId) {
            throw new \RuntimeException('Не може да изтриете собствения си акаунт.');
        }
        $user = self::find($id);
        if (!$user) throw new \RuntimeException('Потребителят не е намерен.');

        if ($user['role'] === 'admin' && self::adminCount() <= 1) {
            throw new \RuntimeException('Не може да изтриете последния администратор.');
        }
        Db::execute('DELETE FROM users WHERE id = ?', [$id]);
    }

    /**
     * Validate user data. Returns errors array (empty == ok).
     * For updates, pass currentId to skip "email taken" against the same user
     * and to enforce the "don't demote the last admin" rule.
     */
    public static function validate(array $data, bool $isCreate, ?int $currentId = null): array
    {
        $errors = [];
        $email  = trim((string) ($data['email'] ?? ''));
        $name   = trim((string) ($data['name'] ?? ''));
        $role   = (string) ($data['role'] ?? '');
        $password = (string) ($data['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Невалиден имейл.';
        } else {
            $existing = self::findByEmail($email);
            if ($existing && (int) $existing['id'] !== $currentId) {
                $errors['email'] = 'Имейлът вече е зает.';
            }
        }
        if (mb_strlen($name) < 2) $errors['name'] = 'Въведете име.';
        if (!in_array($role, self::ROLES, true)) $errors['role'] = 'Невалидна роля.';

        if ($isCreate) {
            if (strlen($password) < 8) $errors['password'] = 'Минимум 8 символа.';
        } else {
            if ($password !== '' && strlen($password) < 8) {
                $errors['password'] = 'Минимум 8 символа.';
            }
        }

        // Last-admin demotion guard
        if (!$isCreate && $currentId && $role !== 'admin') {
            $current = self::find($currentId);
            if ($current && $current['role'] === 'admin' && self::adminCount() <= 1) {
                $errors['role'] = 'Не може да понижите последния администратор.';
            }
        }

        return $errors;
    }

    /**
     * Self-service password change. Verifies current password, then updates.
     */
    public static function changeOwnPassword(int $userId, string $current, string $new): array
    {
        $errors = [];
        $row = Db::fetchOne('SELECT password_hash FROM users WHERE id = ?', [$userId]);
        if (!$row) {
            return ['current' => 'Акаунтът не е намерен.'];
        }
        if (!password_verify($current, $row['password_hash'])) {
            $errors['current'] = 'Текущата парола е грешна.';
        }
        if (strlen($new) < 8) {
            $errors['new'] = 'Минимум 8 символа.';
        }
        if ($errors) return $errors;

        Db::execute(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [Auth::hash($new), $userId]
        );
        return [];
    }

    /**
     * Update own profile (name + email). No role / password here.
     */
    public static function updateOwnProfile(int $userId, string $name, string $email): array
    {
        $errors = [];
        $name  = trim($name);
        $email = trim($email);

        if (mb_strlen($name) < 2) $errors['name'] = 'Въведете име.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Невалиден имейл.';
        } else {
            $existing = self::findByEmail($email);
            if ($existing && (int) $existing['id'] !== $userId) {
                $errors['email'] = 'Имейлът вече е зает.';
            }
        }
        if ($errors) return $errors;

        Db::execute(
            'UPDATE users SET name = ?, email = ? WHERE id = ?',
            [$name, $email, $userId]
        );
        return [];
    }
}
