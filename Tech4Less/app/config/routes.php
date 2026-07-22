<?php

/** @var Router $router */

// --- Frontend pubblico ---
$router->get('/',                          'HomeController@index');
$router->get('/catalogo',                  'CatalogoController@index');
$router->get('/catalogo/{categoria}',      'CatalogoController@perCategoria');
$router->get('/prodotto/{slug}',           'CatalogoController@dettaglio');

$router->get('/carrello',                  'CarrelloController@index');
$router->post('/carrello/aggiungi',        'CarrelloController@aggiungi');
$router->post('/carrello/rimuovi',         'CarrelloController@rimuovi');
$router->post('/carrello/aggiorna',        'CarrelloController@aggiorna');

$router->get('/checkout',                  'CheckoutController@index');
$router->post('/checkout/conferma',        'CheckoutController@conferma');
$router->get('/checkout/riepilogo/{numero}', 'CheckoutController@riepilogo');

$router->get('/account/login',             'AccountController@loginForm');
$router->post('/account/login',            'AccountController@login');
$router->get('/account/registrati',        'AccountController@registratiForm');
$router->post('/account/registrati',       'AccountController@registrati');
$router->post('/account/logout',           'AccountController@logout');
$router->get('/account',                   'AccountController@profilo');
$router->get('/account/ordini',            'AccountController@ordini');
$router->get('/account/wishlist',          'AccountController@wishlist');
$router->post('/account/wishlist/aggiungi','AccountController@wishlistAggiungi');

$router->post('/recensioni/{prodottoId}',  'RecensioniController@salva');

$router->get('/chi-siamo',                 'HomeController@chiSiamo');
$router->get('/faq',                       'HomeController@faq');
$router->post('/contatti',                 'HomeController@contattaci');

// --- Backend amministrativo (montato sotto /admin, vedi public/admin/index.php) ---
$router->get('/admin',                     'admin\\DashboardController@index');
$router->get('/admin/prodotti',            'admin\\ProdottiController@index');
$router->get('/admin/prodotti/nuovo',      'admin\\ProdottiController@creaForm');
$router->post('/admin/prodotti/nuovo',     'admin\\ProdottiController@crea');
$router->get('/admin/prodotti/{id}/modifica', 'admin\\ProdottiController@modificaForm');
$router->post('/admin/prodotti/{id}/modifica','admin\\ProdottiController@modifica');
$router->post('/admin/prodotti/{id}/elimina', 'admin\\ProdottiController@elimina');

$router->get('/admin/ordini',              'admin\\OrdiniController@index');
$router->get('/admin/ordini/{id}',         'admin\\OrdiniController@dettaglio');
$router->post('/admin/ordini/{id}/stato',  'admin\\OrdiniController@aggiornaStato');

$router->get('/admin/utenti',              'admin\\UtentiController@index');
$router->get('/admin/utenti/{id}/gruppi',  'admin\\UtentiController@modificaGruppiForm');
$router->post('/admin/utenti/{id}/gruppi', 'admin\\UtentiController@aggiornaGruppi');

$router->get('/admin/messaggi',            'admin\\MessaggiController@index');
$router->get('/admin/messaggi/{id}',       'admin\\MessaggiController@dettaglio');
$router->post('/admin/messaggi/{id}/stato','admin\\MessaggiController@aggiornaStato');

$router->get('/admin/log',                 'admin\\LogController@index');
