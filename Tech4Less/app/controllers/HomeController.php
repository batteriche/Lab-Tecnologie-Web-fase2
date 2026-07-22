<?php

class HomeController extends Controller
{
    public function index(): void
    {
        $productModel  = new Product();
        $categoryModel = new Category();

        $prodotti  = $this->prepareProducts($productModel->inEvidenza(4));
        $categorie = array_map(function ($c) {
            return [
                'categoria_nome_lista' => $c['nome'],
                'categoria_slug'       => $c['slug'],
            ];
        }, $categoryModel->principali());

        $this->render('frontend/home', [
            'header'    => $this->renderPartial('layout/header', $this->headerData('home')),
            'footer'    => $this->renderPartial('layout/footer', []),
            'categorie' => $categorie,
            'prodotti'  => $prodotti,
        ]);
    }

    public function chiSiamo(): void
    {
        $this->render('frontend/chi-siamo', [
            'header'     => $this->renderPartial('layout/header', $this->headerData('chi-siamo')),
            'footer'     => $this->renderPartial('layout/footer', []),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function faq(): void
    {
        $stmt = Database::query(
            'SELECT domanda, risposta FROM faqs WHERE pubblicata = 1 ORDER BY ordine ASC'
        );

        $this->render('frontend/faq', [
            'header' => $this->renderPartial('layout/header', $this->headerData('faq')),
            'footer' => $this->renderPartial('layout/footer', []),
            'faq'    => $stmt->fetchAll(),
        ]);
    }

    public function contattaci(): void
    {
        $this->verifyCsrf();

        $validator = new Validator();
        $validator->required($_POST, 'nome', 'Nome')
                  ->required($_POST, 'email', 'Email')
                  ->email($_POST, 'email')
                  ->required($_POST, 'oggetto', 'Oggetto')
                  ->required($_POST, 'testo', 'Messaggio');

        if ($validator->fails()) {
            $this->flash('errore', 'Controlla i campi del modulo e riprova.');
            $this->redirect('/chi-siamo');
            return;
        }

        Database::query(
            'INSERT INTO contact_messages (nome, email, oggetto, testo) VALUES (?, ?, ?, ?)',
            [$this->input('nome'), $this->input('email'), $this->input('oggetto'), $this->input('testo')]
        );

        $this->flash('successo', 'Messaggio inviato, ti risponderemo al più presto.');
        $this->redirect('/chi-siamo');
    }

    /**
     * Converte i prodotti grezzi dal model in stringhe già pronte per la view
     * (il template non fa formattazioni: i prezzi arrivano già in "€ 24,90").
     */
    private function prepareProducts(array $prodotti): array
    {
        return array_map(function ($p) {
            $prezzoOld = $p['prezzo_scontato'] !== null ? Product::formatPrezzo((float) $p['prezzo_scontato']) : '';

            $p['prezzo']         = Product::formatPrezzo((float) $p['prezzo']);
            $p['badge_sconto']   = $prezzoOld !== '' ? ' <span class="old">' . $prezzoOld . '</span>' : '';
            $p['badge_refurb']   = $p['condizione'] === 'ricondizionato'
                ? '<span class="tag-refurb">Ricondizionato</span>'
                : '';
            unset($p['prezzo_scontato']);
            return $p;
        }, $prodotti);
    }
}
