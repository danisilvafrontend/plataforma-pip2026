<?php
class UserModel {
    protected $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function getAll(int $page = 1, int $perPage = 15, string $q = ''): array {
        $offset = max(0, ($page - 1) * $perPage);
        $params = [];
        $where = '1=1';
        if ($q !== '') {
            $where = '(nome LIKE :q OR email LIKE :q)';
            $params[':q'] = '%'.$q.'%';
        }
        // monta LIMIT/OFFSET com inteiros injetados para evitar binding issues
        $limit = (int)$perPage;
        $off = (int)$offset;
        $sql = "SELECT id, nome, email, role, status FROM users WHERE {$where} ORDER BY id DESC LIMIT {$limit} OFFSET {$off}";
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k=>$v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('UserModel::getAll error: '.$e->getMessage().' SQL: '.$sql);
            return [];
        }
    }

    public function count(string $q = ''): int {
        $params = [];
        $where = '1=1';
        if ($q !== '') {
            $where = '(nome LIKE :q OR email LIKE :q)';
            $params[':q'] = '%'.$q.'%';
        }
        $sql = "SELECT COUNT(*) FROM users WHERE {$where}";
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k=>$v) $stmt->bindValue($k,$v, PDO::PARAM_STR);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('UserModel::count error: '.$e->getMessage().' SQL: '.$sql);
            return 0;
        }
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create(array $data): int {
        $sql = "INSERT INTO users (nome, email, senha_hash, role, status, created_at) VALUES (:nome, :email, :senha_hash, :role, :status, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
        $stmt->execute([
            ':nome'=>$data['nome'],
            ':email'=>$data['email'],
            ':senha_hash'=>$senha_hash,
            ':role'=>$data['role'],
            ':status'=>$data['status']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $params = [':id'=>$id, ':nome'=>$data['nome'], ':email'=>$data['email'], ':role'=>$data['role'], ':status'=>$data['status']];
        $sql = "UPDATE users SET nome = :nome, email = :email, role = :role, status = :status";
        if (!empty($data['senha'])) {
            $sql .= ", senha_hash = :senha_hash";
            $params[':senha_hash'] = password_hash($data['senha'], PASSWORD_DEFAULT);
        }
        $sql .= " WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (Throwable $e) {
            error_log('UserModel::update error: '.$e->getMessage().' SQL: '.$sql);
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
            return $stmt->execute([':id'=>$id]);
        } catch (Throwable $e) {
            error_log('UserModel::delete error: '.$e->getMessage());
            return false;
        }
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT id, nome, email, role, status FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id'=>$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }
}