<?php

class AccountController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/account');
            return;
        }

        $this->render('frontend/account-login', [
            'header'     => $this->renderPartial('layout/header', $this->headerData('account')),
            'footer'     => $this->renderPartial('layout/footer', []),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function login(): void
    {
        $this->verifyCsrf();

        $email    = $this->input('email');
        $password = $this->input('password');

        if (Auth::attempt($email, $password)) {
            $this->flash('successo', 'Accesso effettuato.');
            $this->redirect('/account');
            return;
        }

        $this->flash('errore', 'Email o password non corrette.');
        $this->redirect('/account/login');
    }

    public function registratiForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/account');
            return;
        }

        $this->render('frontend/account-registrati', [
            'header'     => $this->renderPartial('layout/header', $this->headerData('account')),
            'footer'     => $this->renderPartial('layout/footer', []),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function registrati(): void
    {
        $this->verifyCsrf();

        $validator = new Validator();
        $validator->required($_POST, 'username', 'Username')
                  ->required($_POST, 'email', 'Email')
                  ->email($_POST, 'email')
                  ->required($_POST, 'password', 'Password')
                  ->minLength($_POST, 'password', 8, 'La password')
                  ->required($_POST, 'nome', 'Nome')
                  ->required($_POST, 'cognome', 'Cognome');

        if ($validator->fails()) {
            $this->flash('errore', 'Controlla i campi del modulo: ' . implode(' ', $validator->errors()));
            $this->redirect('/account/registrati');
            return;
        }

        // Unicità email/username: lasciamo che sia il vincolo UNIQUE del DB a farlo rispettare,
        // ma la intercettiamo qui per un messaggio utile invece di un errore SQL grezzo.
        $stmt = Database::query('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1', [
            $this->input('email'), $this->input('username'),
        ]);
        if ($stmt->fetch()) {
            $this->flash('errore', 'Email o username già registrati.');
            $this->redirect('/account/registrati');
            return;
        }

        $userId = Auth::register([
            'username' => $this->input('username'),
            'email'    => $this->input('email'),
            'password' => $this->input('password'),
            'nome'     => $this->input('nome'),
            'cognome'  => $this->input('cognome'),
            'telefono' => $this->input('telefono'),
        ]);

        Auth::attempt($this->input('email'), $this->input('password'));

        $this->flash('successo', 'Registrazione completata, benvenuto su TECH4LESS.');
        $this->redirect('/account');
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        Auth::logout();
        $this->redirect('/');
    }

    public function profilo(): void
    {
        Auth::requireLogin();

        $user = Auth::user();

        $this->render('frontend/account-profilo', [
            'header'   => $this->renderPartial('layout/header', $this->headerData('account')),
            'footer'   => $this->renderPartial('layout/footer', []),
            'nome'     => $user['nome'],
            'cognome'  => $user['cognome'],
            'email'    => $user['email'],
            'username' => $user['username'],
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function ordini(): void
    {
        Auth::requireLogin();

        $orderModel = new Order();
        $ordini = array_map(function ($o) {
            $o['totale'] = Product::formatPrezzo((float) $o['totale']);
            $o['stato_label'] = $this->statoLeggibile($o['stato']);
            return $o;
        }, $orderModel->perUtente(Auth::id()));

        $this->render('frontend/account-ordini', [
            'header'          => $this->renderPartial('layout/header', $this->headerData('account')),
            'footer'          => $this->renderPartial('layout/footer', []),
            'messaggio_vuoto' => count($ordini) === 0 ? 'Non hai ancora effettuato ordini.' : '',
            'ordini'          => $ordini,
        ]);
    }

    public function wishlist(): void
    {
        Auth::requireLogin();

        $wishlistModel = new Wishlist();
        $wishlistId = $wishlistModel->trovaOCreaPerUtente(Auth::id());
        $prodotti = $wishlistModel->prodotti($wishlistId);

        $prodotti = array_map(function ($p) {
            $p['prezzo'] = Product::formatPrezzo((float) ($p['prezzo_scontato'] ?? $p['prezzo']));
            return $p;
        }, $prodotti);

        $this->render('frontend/account-wishlist', [
            'header'          => $this->renderPartial('layout/header', $this->headerData('account')),
            'footer'          => $this->renderPartial('layout/footer', []),
            'messaggio_vuoto' => count($prodotti) === 0 ? 'La tua wishlist è vuota.' : '',
            'prodotti'        => $prodotti,
        ]);
    }

    public function wishlistAggiungi(): void
    {
        Auth::requireLogin();
        $this->verifyCsrf();

        $wishlistModel = new Wishlist();
        $wishlistId = $wishlistModel->trovaOCreaPerUtente(Auth::id());
        $wishlistModel->aggiungi($wishlistId, (int) $this->input('products_id'));

        $this->flash('successo', 'Prodotto aggiunto alla wishlist.');
        $this->redirect('/account/wishlist');
    }

    private function statoLeggibile(string $stato): string
    {
        $mappa = [
            'in_attesa'  => 'In attesa',
            'pagato'     => 'Pagato',
            'spedito'    => 'Spedito',
            'consegnato' => 'Consegnato',
            'annullato'  => 'Annullato',
        ];
        return $mappa[$stato] ?? $stato;
    }
}
