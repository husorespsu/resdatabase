<?php

declare(strict_types=1);

/**
 * PersonnelController
 *
 * Provides JSON API endpoints for HUSO personnel autocomplete.
 */
class PersonnelController extends Controller
{
    /**
     * GET /api/personnel/search?q=keyword
     * Returns JSON array of matching personnel for Select2 AJAX.
     */
    public function search(): void
    {
        // Must be authenticated
        if (!$this->getCurrentUser()) {
            $this->json(['results' => []], 401);
        }

        $q = trim($_GET['q'] ?? '');

        if (mb_strlen($q) < 1) {
            $this->json(['results' => []]);
        }

        $pdo   = DatabaseConfig::getInstance();
        $like  = '%' . $q . '%';

        $stmt = $pdo->prepare(
            "SELECT id, full_name, department, position
             FROM huso_personnel
             WHERE full_name LIKE ?
             ORDER BY full_name
             LIMIT 30"
        );
        $stmt->execute([$like]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Format for Select2 { results: [ {id, text, dept, position} ] }
        $results = array_map(function (array $row): array {
            return [
                'id'       => $row['full_name'],   // use name as id (stored as text)
                'text'     => $row['full_name'],
                'dept'     => $row['department'] ?? '',
                'position' => $row['position']   ?? '',
            ];
        }, $rows);

        $this->json(['results' => $results]);
    }

    /**
     * GET /api/personnel/all
     * Returns all personnel as a flat JSON array (for preloading into select).
     */
    public function all(): void
    {
        if (!$this->getCurrentUser()) {
            $this->json([], 401);
        }

        $pdo  = DatabaseConfig::getInstance();
        $stmt = $pdo->query(
            "SELECT full_name, department FROM huso_personnel ORDER BY full_name"
        );
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $results = array_map(fn(array $r) => [
            'id'   => $r['full_name'],
            'text' => $r['full_name'],
            'dept' => $r['department'] ?? '',
        ], $rows);

        $this->json($results);
    }
}
