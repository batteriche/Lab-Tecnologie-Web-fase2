<?php

class Category
{
    public function tutte(): array
    {
        $stmt = Database::query(
            'SELECT id, nome, slug, parent_id FROM categories ORDER BY nome ASC'
        );
        return $stmt->fetchAll();
    }

    public function principali(): array
    {
        $stmt = Database::query(
            'SELECT id, nome, slug FROM categories WHERE parent_id IS NULL ORDER BY nome ASC'
        );
        return $stmt->fetchAll();
    }

    public function trovaPerSlug(string $slug): ?array
    {
        $stmt = Database::query(
            'SELECT id, nome, slug, parent_id FROM categories WHERE slug = ? LIMIT 1',
            [$slug]
        );
        return $stmt->fetch() ?: null;
    }
}
