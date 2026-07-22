<?php

/**
 * Orchestra la creazione di un ordine a partire dal carrello corrente:
 * valida il coupon, congela i prezzi nelle righe ordine, registra lo stato
 * iniziale e svuota il carrello. Tutto dentro una transazione: o l'ordine
 * si crea per intero, o niente viene scritto.
 */
class OrderService
{
    private Order $orderModel;
    private Coupon $couponModel;
    private Cart $cartModel;

    public function __construct()
    {
        $this->orderModel  = new Order();
        $this->couponModel = new Coupon();
        $this->cartModel   = new Cart();
    }

    /**
     * @throws InvalidArgumentException se il carrello è vuoto o il coupon non è valido
     */
    public function creaOrdine(int $userId, int $addressId, ?string $codiceCoupon = null): string
    {
        $cartService = new CartService();
        $cartId      = $cartService->idCarrelloCorrente();
        $righeCarrello = $this->cartModel->righe($cartId);

        if (count($righeCarrello) === 0) {
            throw new InvalidArgumentException('Il carrello è vuoto.');
        }

        $subtotale = array_reduce($righeCarrello, function ($acc, $r) {
            return $acc + ((float) $r['prezzo_unitario']) * ((int) $r['quantita']);
        }, 0.0);

        $coupon = null;
        $sconto = 0.0;

        if (!empty($codiceCoupon)) {
            $coupon = $this->couponModel->trovaValido($codiceCoupon);
            if (!$coupon) {
                throw new InvalidArgumentException('Codice sconto non valido o scaduto.');
            }
            $sconto = $this->couponModel->calcolaSconto($coupon, $subtotale);
        }

        $totale = max(0, $subtotale - $sconto);

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $numeroOrdine = $this->orderModel->generaNumeroOrdine();

            $orderId = $this->orderModel->crea([
                'users_id'      => $userId,
                'addresses_id'  => $addressId,
                'coupons_id'    => $coupon['id'] ?? null,
                'numero_ordine' => $numeroOrdine,
                'subtotale'     => $subtotale,
                'sconto'        => $sconto,
                'totale'        => $totale,
            ]);

            foreach ($righeCarrello as $riga) {
                $this->orderModel->aggiungiRiga(
                    $orderId,
                    (int) $riga['products_id'],
                    $riga['nome'],
                    (int) $riga['quantita'],
                    (float) $riga['prezzo_unitario']
                );
            }

            $this->orderModel->registraStato($orderId, 'in_attesa', 'Ordine creato dal cliente.');

            if ($coupon) {
                $this->couponModel->incrementaUtilizzo((int) $coupon['id']);
            }

            $this->cartModel->svuota($cartId);

            $db->commit();

            return $numeroOrdine;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
