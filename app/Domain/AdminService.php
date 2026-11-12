<?php
declare(strict_types=1);

namespace Robo\Domain;

use PDO;
use Robo\Core\AuditLogger;
use RuntimeException;
use PDOException;

final class AdminService
{
    public function __construct(private readonly PDO $pdo, private readonly AuditLogger $audit)
    {
    }

    /** @return list<array{code:string,label_cs:string,label_en:string,seconds:int}> */
    public static function defaultPenalties(): array
    {
        return [
            ['code' => 'technical', 'label_cs' => 'Technická přejímka', 'label_en' => 'Technical inspection', 'seconds' => 90],
            ['code' => 'not_stopped', 'label_cs' => 'Nezastavení', 'label_en' => 'Did not stop', 'seconds' => 5],
        ];
    }

    public function competitions(): array
    {
        return $this->pdo->query('SELECT id, competition_year, competition_date, locale, team_count, title_cs, title_en, is_active, created_at FROM competitions ORDER BY competition_year DESC')->fetchAll();
    }

    public function saveCompetition(int $id, int $year, string $date, string $locale, int $teamCount, int $userId, string $ip): void
    {
        $value = AdminValidation::competition($year, $date, $locale, $teamCount);
        $this->pdo->beginTransaction();
        try {
            $current = $this->pdo->prepare('SELECT team_count FROM competitions WHERE id = :id FOR UPDATE');
            $current->execute(['id' => $id]);
            $previous = $current->fetchColumn();
            if ($previous === false) {
                throw new RuntimeException('Ročník nebyl nalezen.');
            }
            $oldCount = (int) $previous;
            if ($teamCount < $oldCount) {
                $hasData = $this->pdo->prepare('SELECT COUNT(*) FROM rides r JOIN teams t ON t.id = r.team_id WHERE t.competition_id = :competition_id AND t.team_number > :team_count');
                $hasData->execute(['competition_id' => $id, 'team_count' => $teamCount]);
                if ((int) $hasData->fetchColumn() > 0) {
                    throw new RuntimeException('Počet týmů nelze snížit, protože vyřazované týmy již mají zapsané jízdy.');
                }
            }
            $update = $this->pdo->prepare('UPDATE competitions SET competition_year=:year, competition_date=:date, locale=:locale, team_count=:team_count WHERE id=:id');
            $update->execute(['year' => $value['year'], 'date' => $value['date'], 'locale' => $value['locale'], 'team_count' => $value['team_count'], 'id' => $id]);
            if ($teamCount > $oldCount) {
                $insert = $this->pdo->prepare('INSERT INTO teams (competition_id, team_number) VALUES (:competition_id, :team_number)');
                for ($number = $oldCount + 1; $number <= $teamCount; $number++) {
                    $insert->execute(['competition_id' => $id, 'team_number' => $number]);
                }
            } elseif ($teamCount < $oldCount) {
                $delete = $this->pdo->prepare('DELETE FROM teams WHERE competition_id=:competition_id AND team_number > :team_count');
                $delete->execute(['competition_id' => $id, 'team_count' => $teamCount]);
            }
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
        $this->audit->log($userId, $id, 'system.competition_saved', 'Nastavení ročníku bylo uloženo.', ['team_count' => $teamCount, 'locale' => $locale], $ip);
    }

    public function activateCompetition(int $id, int $userId, string $ip): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('UPDATE competitions SET is_active = 0');
            $activate = $this->pdo->prepare('UPDATE competitions SET is_active = 1 WHERE id = :id');
            $activate->execute(['id' => $id]);
            if ($activate->rowCount() !== 1) {
                throw new RuntimeException('Ročník nebyl nalezen.');
            }
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
        $this->audit->log($userId, $id, 'system.competition_activated', 'Byl aktivován ročník soutěže.', [], $ip);
    }

    public function createCompetition(int $year, string $date, string $locale, int $teamCount, int $userId, string $ip): int
    {
        $value = AdminValidation::competition($year, $date, $locale, $teamCount);
        $this->pdo->beginTransaction();
        try {
            $insert = $this->pdo->prepare('INSERT INTO competitions (competition_year, competition_date, locale, team_count, is_active) VALUES (:year, :date, :locale, :team_count, 0)');
            $insert->execute($value);
            $competitionId = (int) $this->pdo->lastInsertId();
            $teams = $this->pdo->prepare('INSERT INTO teams (competition_id, team_number) VALUES (:competition_id, :team_number)');
            for ($number = 1; $number <= $teamCount; $number++) {
                $teams->execute(['competition_id' => $competitionId, 'team_number' => $number]);
            }
            $this->ensurePenalties($competitionId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
        $this->audit->log($userId, $competitionId, 'system.competition_created', 'Byl vytvořen nový ročník soutěže.', ['year' => $year], $ip);
        return $competitionId;
    }

    public function pageTexts(int $competitionId): array
    {
        $query = $this->pdo->prepare('SELECT page_key, text_key, locale, text_value FROM page_texts WHERE competition_id=:competition_id ORDER BY page_key, text_key, locale');
        $query->execute(['competition_id' => $competitionId]);
        return $query->fetchAll();
    }

    public function savePageText(int $competitionId, string $page, string $key, string $locale, string $text, int $userId, string $ip): void
    {
        $textLength = function_exists('mb_strlen') ? mb_strlen($text) : preg_match_all('/./us', $text);
        if (!preg_match('/^[a-z0-9_-]{2,80}$/', $page) || !preg_match('/^[a-z0-9_-]{2,80}$/', $key) || !in_array($locale, ['cs', 'en'], true) || trim($text) === '' || $textLength > 255) {
            throw new RuntimeException('Text stránky není platný.');
        }
        $save = $this->pdo->prepare('INSERT INTO page_texts (competition_id,page_key,text_key,locale,text_value) VALUES (:competition_id,:page_key,:text_key,:locale,:text_value) ON DUPLICATE KEY UPDATE text_value=VALUES(text_value)');
        $save->execute(['competition_id' => $competitionId, 'page_key' => $page, 'text_key' => $key, 'locale' => $locale, 'text_value' => trim($text)]);
        $this->audit->log($userId, $competitionId, 'system.page_text_saved', 'Byl uložen text stránky.', ['page' => $page, 'key' => $key, 'locale' => $locale], $ip);
    }

    public function penalties(int $competitionId): array
    {
        $this->ensurePenalties($competitionId);
        $query = $this->pdo->prepare('SELECT id, code, label_cs, label_en, seconds_value FROM penalties WHERE competition_id=:competition_id ORDER BY id');
        $query->execute(['competition_id' => $competitionId]);
        return $query->fetchAll();
    }

    private function ensurePenalties(int $competitionId): void
    {
        $insert = $this->pdo->prepare('INSERT INTO penalties (competition_id,code,label_cs,label_en,seconds_value) VALUES (:competition_id,:code,:label_cs,:label_en,:seconds) ON DUPLICATE KEY UPDATE code=VALUES(code)');
        foreach (self::defaultPenalties() as $penalty) {
            $insert->execute(['competition_id' => $competitionId] + $penalty);
        }
    }

    public function savePenalty(int $competitionId, string $code, string $cs, string $en, int $seconds, int $userId, string $ip): void
    {
        $value = AdminValidation::penalty($code, $cs, $en, $seconds);
        $save = $this->pdo->prepare('INSERT INTO penalties (competition_id,code,label_cs,label_en,seconds_value) VALUES (:competition_id,:code,:label_cs,:label_en,:seconds) ON DUPLICATE KEY UPDATE label_cs=VALUES(label_cs),label_en=VALUES(label_en),seconds_value=VALUES(seconds_value)');
        $save->execute(['competition_id' => $competitionId] + $value);
        $this->audit->log($userId, $competitionId, 'system.penalty_saved', 'Byla uložena penalizace.', ['code' => $code, 'seconds' => $seconds], $ip);
    }

    public function roles(): array
    {
        return $this->pdo->query('SELECT code, label_cs, label_en FROM roles ORDER BY label_cs')->fetchAll();
    }

    public function users(): array
    {
        return $this->pdo->query('SELECT id, username, role_code, is_active, last_login_at, created_at FROM users ORDER BY username')->fetchAll();
    }

    public function saveUser(?int $id, string $username, string $role, bool $active, ?string $password, int $actorId, string $ip): void
    {
        $username = AdminValidation::username($username);
        $roleCheck = $this->pdo->prepare('SELECT COUNT(*) FROM roles WHERE code=:code');
        $roleCheck->execute(['code' => $role]);
        if ((int) $roleCheck->fetchColumn() !== 1) {
            throw new RuntimeException('Vybraná role neexistuje.');
        }
        if ($id === null) {
            if ($password === null || $password === '') {
                throw new RuntimeException('Nový uživatel potřebuje heslo o délce alespoň 12 znaků.');
            }
            $password = AdminValidation::password($password);
            $save = $this->pdo->prepare('INSERT INTO users (username,password_hash,role_code,is_active) VALUES (:username,:password_hash,:role_code,:is_active)');
            try {
                $save->execute(['username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'role_code' => $role, 'is_active' => $active ? 1 : 0]);
            } catch (PDOException $exception) {
                if ((string) $exception->getCode() === '23000') throw new RuntimeException('Uživatel s tímto jménem již existuje.', 0, $exception);
                throw $exception;
            }
            $targetId = (int) $this->pdo->lastInsertId();
        } else {
            if ($id === $actorId && !$active) {
                throw new RuntimeException('Nelze zakázat vlastní přihlášený účet.');
            }
            $sql = 'UPDATE users SET username=:username, role_code=:role_code, is_active=:is_active';
            $params = ['username' => $username, 'role_code' => $role, 'is_active' => $active ? 1 : 0, 'id' => $id];
            if ($password !== null && $password !== '') {
                $password = AdminValidation::password($password);
                $sql .= ', password_hash=:password_hash';
                $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id=:id';
            $save = $this->pdo->prepare($sql);
            try {
                $save->execute($params);
            } catch (PDOException $exception) {
                if ((string) $exception->getCode() === '23000') throw new RuntimeException('Uživatel s tímto jménem již existuje.', 0, $exception);
                throw $exception;
            }
            $targetId = $id;
        }
        $this->audit->log($actorId, null, 'system.user_saved', 'Byl uložen uživatelský účet.', ['target_user_id' => $targetId, 'active' => $active], $ip);
    }

    public function deleteUser(int $id, int $actorId, string $ip): void
    {
        if ($id === $actorId) throw new RuntimeException('Vlastní přihlášený účet nelze smazat.');
        $query = $this->pdo->prepare('SELECT username, role_code, is_active FROM users WHERE id=:id');
        $query->execute(['id' => $id]);
        $account = $query->fetch();
        if ($account === false) throw new RuntimeException('Uživatel nebyl nalezen.');
        if ($account['role_code'] === 'administrator' && (int) $account['is_active'] === 1) {
            $count = (int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE role_code='administrator' AND is_active=1")->fetchColumn();
            if ($count <= 1) throw new RuntimeException('Poslední aktivní účet správce nelze smazat.');
        }
        $scores = $this->pdo->prepare('SELECT COUNT(*) FROM judging_scores WHERE judge_user_id=:id');
        $scores->execute(['id' => $id]);
        if ((int) $scores->fetchColumn() > 0) throw new RuntimeException('Uživatele nelze smazat, protože je autorem bodového hodnocení. Nejprve účet zakažte.');
        $this->audit->log($actorId, null, 'system.user_deleted', 'Uživatelský účet byl smazán.', ['target_user_id' => $id, 'username' => (string) $account['username']], $ip);
        $delete = $this->pdo->prepare('DELETE FROM users WHERE id=:id');
        $delete->execute(['id' => $id]);
    }

    public function changeOwnPassword(int $userId, string $currentPassword, string $newPassword, string $confirmation, string $ip): void
    {
        if ($newPassword !== $confirmation) throw new RuntimeException('Nové heslo a jeho potvrzení se neshodují.');
        $query = $this->pdo->prepare('SELECT password_hash FROM users WHERE id=:id AND is_active=1');
        $query->execute(['id' => $userId]);
        $hash = $query->fetchColumn();
        if (!is_string($hash) || !password_verify($currentPassword, $hash)) throw new RuntimeException('Současné heslo není správné.');
        $newPassword = AdminValidation::password($newPassword);
        if (password_verify($newPassword, $hash)) throw new RuntimeException('Nové heslo musí být jiné než současné heslo.');
        $update = $this->pdo->prepare('UPDATE users SET password_hash=:password_hash WHERE id=:id');
        $update->execute(['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId]);
        $this->audit->log($userId, null, 'security.password_changed', 'Uživatel změnil své heslo.', [], $ip);
    }

    public function audit(string $search, int $page, int $perPage = 100): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = 'WHERE a.message LIKE :message OR a.event_type LIKE :event_type OR u.username LIKE :username OR a.ip_address LIKE :ip OR a.context_json LIKE :context';
            $limitedSearch = function_exists('mb_substr') ? mb_substr($search, 0, 100) : implode('', array_slice(preg_split('//u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 100));
            $term = '%' . $limitedSearch . '%';
            $params = ['message' => $term, 'event_type' => $term, 'username' => $term, 'ip' => $term, 'context' => $term];
        }
        $count = $this->pdo->prepare('SELECT COUNT(*) FROM audit_log a LEFT JOIN users u ON u.id=a.user_id ' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $query = $this->pdo->prepare('SELECT a.id,a.created_at,a.user_id,a.competition_id,a.event_type,a.message,a.context_json,a.ip_address,u.username FROM audit_log a LEFT JOIN users u ON u.id=a.user_id ' . $where . ' ORDER BY a.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset);
        $query->execute($params);
        return ['rows' => $query->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }
}
