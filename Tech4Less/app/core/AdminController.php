<?php

/**
 * Base comune per tutti i controller sotto app/controllers/admin/.
 * Centralizza sidebar, topbar (con messaggi flash) e il controllo permessi
 * via PermissionService, così ogni controller admin non deve ripetere
 * la stessa impalcatura.
 */
abstract class AdminController extends Controller
{
    /**
     * Da chiamare all'inizio di ogni azione: verifica che l'utente loggato
     * appartenga a un gruppo abilitato al servizio richiesto.
     */
    protected function richiediPermesso(string $servizio): void
    {
        PermissionService::require($servizio);
    }

    protected function sidebar(string $paginaAttiva): string
    {
        $voci = ['dashboard', 'prodotti', 'ordini', 'utenti', 'messaggi', 'log'];
        $dati = [];
        foreach ($voci as $voce) {
            $dati['classe_' . $voce] = $voce === $paginaAttiva ? 'active' : '';
        }

        $dati['messaggi_nuovi'] = (new ContactMessage())->contaNuovi();

        return $this->renderPartial('admin/layout/sidebar', $dati);
    }

    protected function topbar(string $titoloPagina): string
    {
        $user = Auth::user();

        return $this->renderPartial('admin/layout/topbar', [
            'titolo_pagina' => $titoloPagina,
            'nome_admin'    => $user['nome'] ?? '',
            'csrf_token'    => $this->csrfToken(),
        ]);
    }

    /**
     * Scorciatoia: costruisce shell (sidebar+topbar) e la unisce ai dati specifici
     * della vista, poi chiama render() come nel Controller base.
     */
    protected function renderAdmin(string $viewPath, string $paginaAttiva, string $titoloPagina, array $data = []): void
    {
        $this->render($viewPath, array_merge([
            'sidebar' => $this->sidebar($paginaAttiva),
            'topbar'  => $this->topbar($titoloPagina),
        ], $data));
    }
}
