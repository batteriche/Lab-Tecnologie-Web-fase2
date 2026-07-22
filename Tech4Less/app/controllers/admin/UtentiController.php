<?php

namespace admin;

use AdminController;
use User;
use ActivityLog;
use Auth;

class UtentiController extends AdminController
{
    public function index(): void
    {
        $this->richiediPermesso('gestione_utenti');

        $userModel = new User();
        $utenti = $userModel->tutti();

        $righe = [];
        foreach ($utenti as $u) {
            $righe[] = [
                'id'       => $u['id'],
                'username' => $u['username'],
                'email'    => $u['email'],
                'nome'     => $u['nome'] . ' ' . $u['cognome'],
                'stato'    => $u['stato'],
                'gruppi'   => $u['gruppi'] ?: '—',
            ];
        }

        $this->renderAdmin('admin/utenti-index', 'utenti', 'Utenti', [
            'utenti' => $righe,
        ]);
    }

    public function modificaGruppiForm(string $id): void
    {
        $this->richiediPermesso('gestione_utenti');

        $userModel = new User();
        $utente = $userModel->trova((int) $id);

        if (!$utente) {
            http_response_code(404);
            require VIEWS_PATH . 'frontend/404.html';
            return;
        }

        $gruppiUtente = $userModel->gruppiDiUtente((int) $id);

        $gruppi = array_map(function ($g) use ($gruppiUtente) {
            return [
                'gruppo_id'       => $g['id'],
                'gruppo_nome'     => $g['nome'],
                'gruppo_checked'  => in_array($g['id'], $gruppiUtente, true) ? 'checked' : '',
            ];
        }, $userModel->gruppiDisponibili());

        $this->renderAdmin('admin/utenti-gruppi', 'utenti', 'Gruppi di ' . $utente['nome'], [
            'csrf_token'   => $this->csrfToken(),
            'id'           => $id,
            'nome_utente'  => $utente['nome'] . ' ' . $utente['cognome'],
            'gruppi'       => $gruppi,
        ]);
    }

    public function aggiornaGruppi(string $id): void
    {
        $this->richiediPermesso('gestione_utenti');
        $this->verifyCsrf();

        $gruppi = $_POST['gruppi'] ?? [];
        (new User())->impostaGruppi((int) $id, is_array($gruppi) ? $gruppi : []);

        ActivityLog::registra(Auth::id(), 'utente_gruppi_modificati', 'Utente ID ' . $id);

        $this->flash('successo', 'Gruppi aggiornati.');
        $this->redirect('/admin/utenti');
    }
}
