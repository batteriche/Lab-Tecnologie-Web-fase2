<?php

class CarrelloController extends Controller
{
    public function index(): void
    {
        $cart = new CartService();
        $token = $this->csrfToken();

        $righe = array_map(function ($r) use ($token) {
            $r['csrf_token'] = $token;
            return $r;
        }, $cart->righePerView());

        $this->render('frontend/carrello', [
            'header'          => $this->renderPartial('layout/header', $this->headerData('carrello')),
            'footer'          => $this->renderPartial('layout/footer', []),
            'messaggio_vuoto' => $cart->conteggio() === 0 ? 'Il tuo carrello è vuoto.' : '',
            'righe'           => $righe,
            'totale'          => $cart->totaleFormattato(),
        ]);
    }

    public function aggiungi(): void
    {
        $this->verifyCsrf();

        $productId = (int) $this->input('products_id');
        $quantita  = max(1, (int) $this->input('quantita', 1));

        try {
            (new CartService())->aggiungi($productId, $quantita);
            $this->flash('successo', 'Prodotto aggiunto al carrello.');
        } catch (InvalidArgumentException $e) {
            $this->flash('errore', $e->getMessage());
        }

        $this->redirect('/carrello');
    }

    public function aggiorna(): void
    {
        $this->verifyCsrf();

        $itemId   = (int) $this->input('item_id');
        $quantita = (int) $this->input('quantita', 1);

        (new CartService())->aggiorna($itemId, $quantita);
        $this->redirect('/carrello');
    }

    public function rimuovi(): void
    {
        $this->verifyCsrf();

        $itemId = (int) $this->input('item_id');
        (new CartService())->rimuovi($itemId);
        $this->redirect('/carrello');
    }
}
