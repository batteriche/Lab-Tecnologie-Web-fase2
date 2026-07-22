<?php

class ActivityLog
{
    public static function registra(?int $userId, string $azione, ?string $dettagli = null): void
    {
        Database::query(
            'INSERT INTO activity_log (users_id, azione, dettagli, ip_address) VALUES (?, ?, ?, ?)',
            [$userId, $azione, $dettagli, $_SERVER['REMOTE_ADDR'] ?? null]
        );
    }

    public function recenti(int $limite = 100): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT al.azione, al.dettagli, al.ip_address, al.data_evento, u.username
             FROM activity_log al
             LEFT JOIN users u ON u.id = al.users_id
             ORDER BY al.data_evento DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
