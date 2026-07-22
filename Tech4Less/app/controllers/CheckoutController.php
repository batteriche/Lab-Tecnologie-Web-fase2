<?php

class CheckoutController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $cart = new CartService();

        if ($cart->conteggio() === 0) {
            $this->flash('errore', 'Il tuo carrello è vuoto.');
            $this->redirect('/carrello');
            return;
        }

        $addressModel = new Address();
        $indirizzi = $addressModel->perUtente(Auth::id());

        $this->render('frontend/checkout', [
            'header'       => $this->renderPartial('layout/header', $this->headerData('checkout')),
            'footer'       => $this->renderPartial('layout/footer', []),
            'csrf_token'   => $this->csrfToken(),
            'righe'        => $cart->righePerView(),
            'totale'       => $cart->totaleFormattato(),
            'indirizzi'    => $indirizzi,
        ]);
    }

    public function conferma(): void
    {
        Auth::requireLogin();
        $this->verifyCsrf();

        $addressModel = new Address();
        $addressId = (int) $this->input('addresses_id', 0);

        // Nessun indirizzo salvato selezionato: ne creiamo uno nuovo dal form
        if ($addressId === 0) {
            $validator = new Validator();
            $validator->required($_POST, 'via', 'Indirizzo')
                      ->required($_POST, 'citta', 'Città')
                      ->required($_POST, 'provincia', 'Provincia')
                      ->required($_POST, 'cap', 'CAP');

            if ($validator->fails()) {
                $this->flash('errore', "Compila tutti i campi dell'indirizzo.");
                $this->redirect('/checkout');
                return;
            }

            $addressId = $addressModel->crea(Auth::id(), [
                'etichetta' => $this->input('etichetta', 'Casa'),
                'via'       => $this->input('via'),
                'citta'     => $this->input('citta'),
                'provincia' => strtoupper($this->input('provincia')),
                'cap'       => $this->input('cap'),
                'nazione'   => $this->input('nazione', 'Italia'),
            ]);
        } else {
            $indirizzo = $addressModel->trova($addressId, Auth::id());
            if (!$indirizzo) {
                $this->flash('errore', 'Indirizzo non valido.');
                $this->redirect('/checkout');
                return;
            }
        }

        $codiceCoupon = $this->input('coupon', '');

        try {
            $numeroOrdine = (new OrderService())->creaOrdine(Auth::id(), $addressId, $codiceCoupon ?: null);
        } catch (InvalidArgumentException $e) {
            $this->flash('errore', $e->getMessage());
            $this->redirect('/checkout');
            return;
        }

        $this->redirect('/checkout/riepilogo/' . $numeroOrdine);
    }

    public function riepilogo(string $numero): void
    {
        Auth::requireLogin();

        $orderModel = new Order();
        $ordine = $orderModel->trovaPerNumero($numero, Auth::id());

        if (!$ordine) {
            http_response_code(404);
            require VIEWS_PATH . 'frontend/404.html';
            return;
        }

        $righe = array_map(function ($r) {
            $r['prezzo_unitario_fmt'] = Product::formatPrezzo((float) $r['prezzo_unitario']);
            $r['subtotale_fmt']       = Product::formatPrezzo((float) $r['prezzo_unitario'] * (int) $r['quantita']);
            return $r;
        }, $orderModel->righe($ordine['id']));

        $this->render('frontend/riepilogo', [
            'header'        => $this->renderPartial('layout/header', $this->headerData('checkout')),
            'footer'        => $this->renderPartial('layout/footer', []),
            'numero_ordine' => $ordine['numero_ordine'],
            'subtotale'     => Product::formatPrezzo((float) $ordine['subtotale']),
            'sconto'        => Product::formatPrezzo((float) $ordine['sconto']),
            'totale'        => Product::formatPrezzo((float) $ordine['totale']),
            'righe'         => $righe,
        ]);
    }
}
