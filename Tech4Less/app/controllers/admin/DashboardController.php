<?php

namespace admin;

use AdminController;
use Database;
use PDO;

class DashboardController extends AdminController
{
    public function index(): void
    {
        \Auth::requireLogin();

        $gruppi = \PermissionService::groupsOf(\Auth::id());
        if (!array_intersect($gruppi, ['admin', 'magazzinieri'])) {
            http_response_code(403);
            require VIEWS_PATH . 'frontend/403.html';
            return;
        }

        $totaleOrdini = (int) Database::query('SELECT COUNT(*) AS n FROM orders')->fetch()['n'];
        $totaleProdotti = (int) Database::query('SELECT COUNT(*) AS n FROM products WHERE attivo = 1')->fetch()['n'];
        $totaleUtenti = (int) Database::query('SELECT COUNT(*) AS n FROM users')->fetch()['n'];
        $fatturato = (float) (Database::query("SELECT COALESCE(SUM(totale),0) AS s FROM orders WHERE stato != 'annullato'")->fetch()['s']);

        $ultimiOrdini = Database::query(
            'SELECT o.numero_ordine, o.totale, o.stato, o.data_ordine, u.nome, u.cognome
             FROM orders o JOIN users u ON u.id = o.users_id
             ORDER BY o.data_ordine DESC LIMIT 8'
        )->fetchAll();

        $ultimiOrdini = array_map(function ($o) {
            $o['totale']      = \Product::formatPrezzo((float) $o['totale']);
            $o['cliente']     = $o['nome'] . ' ' . $o['cognome'];
            $o['stato_label'] = $o['stato'];
            return $o;
        }, $ultimiOrdini);

        $this->renderAdmin('admin/dashboard', 'dashboard', 'Dashboard', [
            'totale_ordini'   => $totaleOrdini,
            'totale_prodotti' => $totaleProdotti,
            'totale_utenti'   => $totaleUtenti,
            'fatturato'       => \Product::formatPrezzo($fatturato),
            'ultimi_ordini'   => $ultimiOrdini,
        ]);
    }
}
