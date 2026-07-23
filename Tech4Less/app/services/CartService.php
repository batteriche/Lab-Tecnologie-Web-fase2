<?php

/**
 * Centralizza la logica del carrello: risoluzione carrello ospite/utente,
 * aggiunta/rimozione righe, totali. I controller non parlano mai direttamente
 * con il model Cart, solo con questo service.
 */
class CartService
{
    private Cart $cartModel;

    public function __construct()
    {
        $this->cartModel = new Cart();
    }

    /**
     * Ritorna l'id del carrello corrente (utente loggato o ospite via token di sessione).
     * Se l'utente si è appena autenticato e aveva un carrello ospite, lo unisce
     * automaticamente a quello dell'utente (vedi Auth::attempt).
     */
    public function idCarrelloCorrente(): int
    {
        if (Auth::check()) {
            $cart = $this->cartModel->trovaOCreaPerUtente(Auth::id());
            return (int) $cart['id'];
        }

        return (int) $this->cartOspite()['id'];
    }

    public function aggiungi(int $productId, int $quantita = 1): void
    {
        $cartId = $this->idCarrelloCorrente();

        $stmt = Database::query('SELECT prezzo, prezzo_scontato, giacenza FROM products WHERE id = ? AND attivo = 1', [$productId]);
        $prodotto = $stmt->fetch();

        if (!$prodotto) {
            throw new InvalidArgumentException('Prodotto non disponibile.');
        }

        $prezzoUnitario = (float) ($prodotto['prezzo_scontato'] ?? $prodotto['prezzo']);

        $rigaEsistente = $this->cartModel->trovaRigaProdotto($cartId, $productId);
        $quantitaGiaNelCarrello = $rigaEsistente ? (int) $rigaEsistente['quantita'] : 0;
        $quantitaTotaleRichiesta = $quantitaGiaNelCarrello + $quantita;

        if ($quantitaTotaleRichiesta > (int) $prodotto['giacenza']) {
            throw new InvalidArgumentException(
                'Giacenza insufficiente: disponibili ' . ((int) $prodotto['giacenza'] - $quantitaGiaNelCarrello) . ' pezzi.'
            );
        }

        if ($rigaEsistente) {
            $this->cartModel->aggiornaQuantita((int) $rigaEsistente['id'], $quantitaTotaleRichiesta);
        } else {
            $this->cartModel->aggiungiRiga($cartId, $productId, $quantita, $prezzoUnitario);
        }
    }

    public function aggiorna(int $itemId, int $quantita): void
    {
        if ($quantita <= 0) {
            $this->cartModel->rimuoviRiga($itemId);
            return;
        }

        $this->cartModel->aggiornaQuantita($itemId, $quantita);
    }

    public function rimuovi(int $itemId): void
    {
        $this->cartModel->rimuoviRiga($itemId);
    }

    /**
     * Righe pronte per la view: prezzo e subtotale già formattati in € (il template
     * non fa calcoli), quantità e id riga lasciati come interi per i form di update.
     */
    public function righePerView(): array
    {
        $cartId = $this->idCarrelloCorrente();
        $righe  = $this->cartModel->righe($cartId);

        return array_map(function ($r) {
            $subtotale = ((float) $r['prezzo_unitario']) * ((int) $r['quantita']);
            return [
                'item_id'         => $r['item_id'],
                'nome'            => $r['nome'],
                'slug'            => $r['slug'],
                'quantita'        => $r['quantita'],
                'prezzo_unitario' => Product::formatPrezzo((float) $r['prezzo_unitario']),
                'subtotale'       => Product::formatPrezzo($subtotale),
            ];
        }, $righe);
    }

    public function totale(): float
    {
        $cartId = $this->idCarrelloCorrente();
        $righe  = $this->cartModel->righe($cartId);

        return array_reduce($righe, function ($acc, $r) {
            return $acc + ((float) $r['prezzo_unitario']) * ((int) $r['quantita']);
        }, 0.0);
    }

    public function totaleFormattato(): string
    {
        return Product::formatPrezzo($this->totale());
    }

    /**
     * Numero totale di articoli (somma quantità), per il badge nell'header.
     */
    public function conteggio(): int
    {
        $cartId = $this->idCarrelloCorrente();
        $righe  = $this->cartModel->righe($cartId);

        return (int) array_sum(array_column($righe, 'quantita'));
    }

    /**
     * Da chiamare subito dopo un login riuscito: unisce il carrello ospite
     * (se esiste) dentro il carrello dell'utente appena autenticato.
     */
    public function fondiCarrelloOspiteConUtente(int $userId): void
    {
        if (empty($_SESSION['cart_token'])) {
            return;
        }

        $cartOspite = $this->cartModel->trovaOCreaPerOspite($_SESSION['cart_token']);
        $cartUtente = $this->cartModel->trovaOCreaPerUtente($userId);

        $this->cartModel->unisci((int) $cartOspite['id'], (int) $cartUtente['id']);
        unset($_SESSION['cart_token']);
    }

    private function cartOspite(): array
    {
        if (empty($_SESSION['cart_token'])) {
            $_SESSION['cart_token'] = bin2hex(random_bytes(16));
        }

        return $this->cartModel->trovaOCreaPerOspite($_SESSION['cart_token']);
    }
}
