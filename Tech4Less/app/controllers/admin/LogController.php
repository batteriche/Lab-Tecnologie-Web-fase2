<?php

namespace admin;

use AdminController;
use ActivityLog;

class LogController extends AdminController
{
    public function index(): void
    {
        // Il log di audit è visibile solo a chi gestisce utenti: contiene azioni sensibili
        $this->richiediPermesso('gestione_utenti');

        $eventi = (new ActivityLog())->recenti(150);

        $this->renderAdmin('admin/log-index', 'log', 'Log attività', [
            'eventi' => $eventi,
        ]);
    }
}
