<?php

namespace admin;

use AdminController;
use ContactMessage;
use ActivityLog;
use Auth;

class MessaggiController extends AdminController
{
    public function index(): void
    {
        $this->richiediPermesso('gestione_contenuti');

        $messaggi = (new ContactMessage())->tutti();

        $this->renderAdmin('admin/messaggi-index', 'messaggi', 'Messaggi ricevuti', [
            'messaggi' => $messaggi,
        ]);
    }

    public function dettaglio(string $id): void
    {
        $this->richiediPermesso('gestione_contenuti');

        $messaggioModel = new ContactMessage();
        $messaggio = $messaggioModel->trova((int) $id);

        if (!$messaggio) {
            http_response_code(404);
            require VIEWS_PATH . 'frontend/404.html';
            return;
        }

        if ($messaggio['stato'] === 'nuovo') {
            $messaggioModel->aggiornaStato((int) $id, 'letto');
            $messaggio['stato'] = 'letto';
        }

        $this->renderAdmin('admin/messaggi-dettaglio', 'messaggi', 'Messaggio da ' . $messaggio['nome'], [
            'csrf_token' => $this->csrfToken(),
            'id'         => $messaggio['id'],
            'nome'       => $messaggio['nome'],
            'email'      => $messaggio['email'],
            'oggetto'    => $messaggio['oggetto'],
            'testo'      => $messaggio['testo'],
            'stato'      => $messaggio['stato'],
            'data_invio' => $messaggio['data_invio'],
        ]);
    }

    public function aggiornaStato(string $id): void
    {
        $this->richiediPermesso('gestione_contenuti');
        $this->verifyCsrf();

        $stato = $this->input('stato');
        $ammessi = ['nuovo', 'letto', 'risposto', 'archiviato'];

        if (in_array($stato, $ammessi, true)) {
            (new ContactMessage())->aggiornaStato((int) $id, $stato);
            ActivityLog::registra(Auth::id(), 'messaggio_stato_modificato', 'Messaggio ' . $id . ' -> ' . $stato);
            $this->flash('successo', 'Stato messaggio aggiornato.');
        }

        $this->redirect('/admin/messaggi/' . $id);
    }
}
