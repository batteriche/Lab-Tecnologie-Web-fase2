<?php

class CatalogoController extends Controller
{
    public function index(): void
    {
        $this->mostraCatalogo(null);
    }

    public function perCategoria(string $categoria): void
    {
        $categoryModel = new Category();
        $cat = $categoryModel->trovaPerSlug($categoria);

        if (!$cat) {
            http_response_code(404);
            require VIEWS_PATH . 'frontend/404.html';
            return;
        }

        $this->mostraCatalogo($cat);
    }

    public function dettaglio(string $slug): void
    {
        $productModel = new Product();
        $prodotto = $productModel->trovaPerSlug($slug);

        if (!$prodotto) {
            http_response_code(404);
            require VIEWS_PATH . 'frontend/404.html';
            return;
        }

        $prodotto['prezzo']          = Product::formatPrezzo((float) $prodotto['prezzo']);
        $prodotto['prezzo_scontato'] = $prodotto['prezzo_scontato'] !== null
            ? Product::formatPrezzo((float) $prodotto['prezzo_scontato'])
            : '';

        $specifiche = $productModel->specifiche($prodotto['id']);

        // Calcoliamo se il prodotto è esaurito
        $giacenza = (int) $prodotto['giacenza'];
        $disabledAttr = $giacenza <= 0 ? 'disabled' : '';
        $testoBottone = $giacenza <= 0 ? 'Esaurito' : 'Aggiungi al carrello';

        $this->render('frontend/prodotto', [
            'header'        => $this->renderPartial('layout/header', $this->headerData('catalogo')),
            'footer'        => $this->renderPartial('layout/footer', []),
            'csrf_token'    => $this->csrfToken(),
            'products_id'   => $prodotto['id'],
            'nome'          => $prodotto['nome'],
            'descrizione'   => $prodotto['descrizione'],
            'prezzo'        => $prodotto['prezzo'],
            'condizione'    => $prodotto['condizione'] === 'ricondizionato' ? 'Ricondizionato' : 'Nuovo',
            'immagine'      => $prodotto['immagine'],
            'giacenza'      => $giacenza,
            'disabled_attr' => $disabledAttr, // <-- Passiamo l'attributo 'disabled' se è 0
            'testo_bottone' => $testoBottone, // <-- Cambiamo dinamicamente il testo del bottone
            'specifiche'    => $specifiche,
        ]);
    }

    private function mostraCatalogo(?array $categoriaAttiva): void
    {
        $productModel  = new Product();
        $categoryModel = new Category();

        $prodotti = $categoriaAttiva
            ? $productModel->perCategoria((int) $categoriaAttiva['id'])
            : $productModel->tutti();

        $prodotti = array_map(function ($p) {
            $prezzoOld = $p['prezzo_scontato'] !== null ? Product::formatPrezzo((float) $p['prezzo_scontato']) : '';
            $p['prezzo']        = Product::formatPrezzo((float) $p['prezzo']);
            $p['badge_sconto']  = $prezzoOld !== '' ? ' <span class="old">' . $prezzoOld . '</span>' : '';
            unset($p['prezzo_scontato']);
            return $p;
        }, $prodotti);

        $categorie = array_map(function ($c) {
            return [
                'categoria_nome_lista' => $c['nome'],
                'categoria_slug'       => $c['slug'],
            ];
        }, $categoryModel->principali());

        $this->render('frontend/catalogo', [
            'header'          => $this->renderPartial('layout/header', $this->headerData('catalogo')),
            'footer'          => $this->renderPartial('layout/footer', []),
            'titolo_pagina'   => $categoriaAttiva ? $categoriaAttiva['nome'] : 'Catalogo',
            'messaggio_vuoto' => count($prodotti) === 0 ? 'Nessun prodotto trovato in questa categoria.' : '',
            'categorie'       => $categorie,
            'prodotti'        => $prodotti,
        ]);
    }
}
