<?php

namespace admin;

use AdminController;
use Product;
use Category;
use Validator;
use ActivityLog;
use Auth;

class ProdottiController extends AdminController
{
    public function index(): void
    {
        $this->richiediPermesso('gestione_catalogo');

        $token = $this->csrfToken();

        $prodotti = array_map(function ($p) use ($token) {
            $p['prezzo']           = \Product::formatPrezzo((float) $p['prezzo']);
            $p['stato_label']      = $p['attivo'] ? 'attivo' : 'sospeso';
            $p['stato_testo']      = $p['attivo'] ? 'Attivo' : 'Disattivo';
            $p['csrf_token_riga']  = $token;
            return $p;
        }, (new Product())->tuttiAdmin());

        $this->renderAdmin('admin/prodotti-index', 'prodotti', 'Prodotti', [
            'prodotti' => $prodotti,
        ]);
    }

    public function creaForm(): void
    {
        $this->richiediPermesso('gestione_catalogo');

        $categorie = array_map(function ($c) {
            return ['cat_id' => $c['id'], 'cat_nome' => $c['nome']];
        }, (new Category())->tutte());

        $this->renderAdmin('admin/prodotti-form', 'prodotti', 'Nuovo prodotto', [
            'azione'           => '/admin/prodotti/nuovo',
            'csrf_token'       => $this->csrfToken(),
            'categorie'        => $categorie,
            'immagine_attuale' => '',
            'id'          => '', 'nome' => '', 'slug' => '', 'descrizione' => '',
            'prezzo'      => '', 'prezzo_scontato' => '', 'giacenza' => '0', 'garanzia_mesi' => '24',
        ]);
    }

    public function crea(): void
    {
        $this->richiediPermesso('gestione_catalogo');
        $this->verifyCsrf();

        $errore = $this->validaForm();
        if ($errore) {
            $this->flash('errore', $errore);
            $this->redirect('/admin/prodotti/nuovo');
            return;
        }

        $productModel = new Product();
        $id = $productModel->crea($this->datiForm());

        $this->gestisciImmagineCaricata($productModel, $id);

        ActivityLog::registra(Auth::id(), 'prodotto_creato', 'ID ' . $id . ' — ' . $this->input('nome'));

        $this->flash('successo', 'Prodotto creato.');
        $this->redirect('/admin/prodotti');
    }

    public function modificaForm(string $id): void
    {
        $this->richiediPermesso('gestione_catalogo');

        $prodotto = new Product();
        $datiProdotto = $prodotto->trovaPerId((int) $id);
        if (!$datiProdotto) {
            http_response_code(404);
            require VIEWS_PATH . 'frontend/404.html';
            return;
        }

        $categorie = array_map(function ($c) {
            return ['cat_id' => $c['id'], 'cat_nome' => $c['nome']];
        }, (new Category())->tutte());

        $this->renderAdmin('admin/prodotti-form', 'prodotti', 'Modifica prodotto', [
            'azione'           => '/admin/prodotti/' . $id . '/modifica',
            'csrf_token'       => $this->csrfToken(),
            'categorie'        => $categorie,
            'immagine_attuale' => $prodotto->immaginePrincipale((int) $id),
            'id'              => $datiProdotto['id'],
            'nome'            => $datiProdotto['nome'],
            'slug'            => $datiProdotto['slug'],
            'descrizione'     => $datiProdotto['descrizione'],
            'prezzo'          => $datiProdotto['prezzo'],
            'prezzo_scontato' => $datiProdotto['prezzo_scontato'],
            'giacenza'        => $datiProdotto['giacenza'],
            'garanzia_mesi'   => $datiProdotto['garanzia_mesi'],
        ]);
    }

    public function modifica(string $id): void
    {
        $this->richiediPermesso('gestione_catalogo');
        $this->verifyCsrf();

        $errore = $this->validaForm();
        if ($errore) {
            $this->flash('errore', $errore);
            $this->redirect('/admin/prodotti/' . $id . '/modifica');
            return;
        }

        $productModel = new Product();
        $productModel->aggiorna((int) $id, $this->datiForm());

        $this->gestisciImmagineCaricata($productModel, (int) $id, true);

        ActivityLog::registra(Auth::id(), 'prodotto_modificato', 'ID ' . $id);

        $this->flash('successo', 'Prodotto aggiornato.');
        $this->redirect('/admin/prodotti');
    }

    /**
     * Se è stato caricato un file nel campo 'immagine', lo salva e lo associa
     * al prodotto. In modifica, sostituisce l'immagine precedente.
     */
    private function gestisciImmagineCaricata(Product $productModel, int $productId, bool $sostituisci = false): void
    {
        if (empty($_FILES['immagine']['name'])) {
            return;
        }

        try {
            $percorso = \ImageUploader::gestisci($_FILES['immagine']);
        } catch (\InvalidArgumentException $e) {
            $this->flash('errore', 'Prodotto salvato, ma immagine non caricata: ' . $e->getMessage());
            return;
        }

        if ($percorso === null) {
            return;
        }

        if ($sostituisci) {
            $productModel->rimuoviImmagini($productId);
        }

        $productModel->aggiungiImmagine($productId, $percorso);
    }

    public function elimina(string $id): void
    {
        $this->richiediPermesso('gestione_catalogo');
        $this->verifyCsrf();

        (new Product())->elimina((int) $id);
        ActivityLog::registra(Auth::id(), 'prodotto_eliminato', 'ID ' . $id);

        $this->flash('successo', 'Prodotto eliminato.');
        $this->redirect('/admin/prodotti');
    }

    private function validaForm(): ?string
    {
        $v = new Validator();
        $v->required($_POST, 'nome', 'Nome')
          ->required($_POST, 'categories_id', 'Categoria')
          ->numeric($_POST, 'prezzo', 'Prezzo')
          ->required($_POST, 'prezzo', 'Prezzo');

        return $v->fails() ? implode(' ', $v->errors()) : null;
    }

    private function datiForm(): array
    {
        $nome = $this->input('nome');

        return [
            'categories_id'   => (int) $this->input('categories_id'),
            'brands_id'       => null,
            'nome'            => $nome,
            'slug'            => $this->slugify($nome),
            'descrizione'     => $this->input('descrizione'),
            'prezzo'          => (float) $this->input('prezzo'),
            'prezzo_scontato' => $this->input('prezzo_scontato') !== '' ? (float) $this->input('prezzo_scontato') : null,
            'condizione'      => 'nuovo',
            'giacenza'        => (int) $this->input('giacenza', 0),
            'garanzia_mesi'   => (int) $this->input('garanzia_mesi', 24),
            'attivo'          => 1,
        ];
    }

    private function slugify(string $testo): string
    {
        $slug = strtolower(trim($testo));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-') . '-' . substr(md5(uniqid()), 0, 5);
    }
}
