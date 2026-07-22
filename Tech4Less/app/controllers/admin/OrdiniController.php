<?php

namespace admin;

use AdminController;
use Order;
use Validator;
use ActivityLog;
use Auth;
use Product;

class OrdiniController extends AdminController
{
    public function index(): void
    {
        $this->richiediPermesso('gestione_ordini');

        $ordini = array_map(function ($o) {
            $o['totale']  = Product::formatPrezzo((float) $o['totale']);
            $o['cliente'] = $o['cliente_nome'] . ' ' . $o['cliente_cognome'];
            return $o;
        }, (new Order())->tuttiAdmin());

        $this->renderAdmin('admin/ordini-index', 'ordini', 'Ordini', [
            'ordini' => $ordini,
        ]);
    }

    public function dettaglio(string $id): void
    {
        $this->richiediPermesso('gestione_ordini');

        $orderModel = new Order();
        $ordine = $orderModel->trovaPerId((int) $id);

        if (!$ordine) {
            http_response_code(404);
            require VIEWS_PATH . 'frontend/404.html';
            return;
        }

        $righe = array_map(function ($r) {
            $r['prezzo_unitario'] = Product::formatPrezzo((float) $r['prezzo_unitario']);
            $r['riga_subtotale']  = Product::formatPrezzo((float) $r['prezzo_unitario'] * $r['quantita']);
            return $r;
        }, $orderModel->righe($ordine['id']));

        $storico = $orderModel->storicoStati($ordine['id']);

        $this->renderAdmin('admin/ordini-dettaglio', 'ordini', 'Ordine ' . $ordine['numero_ordine'], [
            'csrf_token'     => $this->csrfToken(),
            'id'             => $ordine['id'],
            'numero_ordine'  => $ordine['numero_ordine'],
            'cliente'        => $ordine['cliente_nome'] . ' ' . $ordine['cliente_cognome'],
            'cliente_email'  => $ordine['cliente_email'],
            'indirizzo'      => $ordine['via'] . ', ' . $ordine['citta'] . ' (' . $ordine['provincia'] . ') ' . $ordine['cap'],
            'stato_corrente' => $ordine['stato'],
            'subtotale'      => Product::formatPrezzo((float) $ordine['subtotale']),
            'sconto'         => Product::formatPrezzo((float) $ordine['sconto']),
            'totale'         => Product::formatPrezzo((float) $ordine['totale']),
            'righe'          => $righe,
            'storico'        => $storico,
        ]);
    }

    public function aggiornaStato(string $id): void
    {
        $this->richiediPermesso('gestione_ordini');
        $this->verifyCsrf();

        $stato = $this->input('stato');
        $ammessi = ['in_attesa', 'pagato', 'spedito', 'consegnato', 'annullato'];

        if (!in_array($stato, $ammessi, true)) {
            $this->flash('errore', 'Stato non valido.');
            $this->redirect('/admin/ordini/' . $id);
            return;
        }

        $orderModel = new Order();
        $orderModel->aggiornaStato((int) $id, $stato);
        $orderModel->registraStato((int) $id, $stato, $this->input('nota', ''), Auth::id());

        ActivityLog::registra(Auth::id(), 'ordine_stato_modificato', 'Ordine ' . $id . ' -> ' . $stato);

        $this->flash('successo', 'Stato ordine aggiornato.');
        $this->redirect('/admin/ordini/' . $id);
    }
}
