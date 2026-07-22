<?php

/**
 * Classe base per tutti i controller: centralizza rendering vista,
 * redirect, lettura input e protezione CSRF.
 */
abstract class Controller
{
    /**
     * Esegue una vista .html (sintassi template2.inc.php: <[var]>, <[foreach]>...<[/foreach]>).
     * La vista non deve mai contenere logica applicativa, solo placeholder.
     *
     * $data accetta due forme di valore per ogni chiave:
     *  - scalare  → setContent($chiave, $valore) una sola volta (placeholder semplice)
     *  - array di array associativi → setContent($campo, $valore) chiamato una volta
     *    per ogni riga, per ogni campo della riga. Alimenta un <[foreach]> nella view.
     *    Esempio: 'prodotti' => [['nome'=>'Arduino','prezzo'=>24.90], ...]
     */
    protected function render(string $viewPath, array $data = []): void
    {
        require_once VIEWS_PATH . 'template2.inc.php';

        $tpl = new Template(VIEWS_PATH . $viewPath); // carica {$viewPath}.html
        $this->bindData($tpl, $data);
        $tpl->close();
    }

    /**
     * Variante che ritorna l'HTML invece di stamparlo (view parziali, es. dentro un frame admin).
     */
    protected function renderPartial(string $viewPath, array $data = []): string
    {
        require_once VIEWS_PATH . 'template2.inc.php';

        $tpl = new Template(VIEWS_PATH . $viewPath);
        $this->bindData($tpl, $data);
        return $tpl->get();
    }

    private function bindData(Template $tpl, array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value) && $this->isListOfRows($value)) {
                // Nome univoco per questo blocco foreach: 'base' è già registrato dal motore
                // a livello globale (fuori da ogni foreach) e non è riusabile qui dentro;
                // e due foreach diversi nella stessa view non possono condividere lo stesso nome.
                $chiaveBase = 'b_' . preg_replace('/[^a-zA-Z0-9_]/', '', $key);

                foreach ($value as $row) {
                    if (!array_key_exists($chiaveBase, $row)) {
                        $row[$chiaveBase] = defined('BASE_URL') ? BASE_URL : '';
                    }
                    foreach ($row as $campo => $valoreCampo) {
                        $tpl->setContent($campo, $valoreCampo);
                    }
                }
            } else {
                $tpl->setContent($key, $value);
            }
        }
    }

    private function isListOfRows(array $value): bool
    {
        $first = reset($value);
        return $first !== false && is_array($first);
    }

    /**
     * Dati standard per il partial layout/header: pagina attiva, conteggio carrello,
     * stato login. Usato da tutti i controller invece di ripetere la stessa logica.
     */
    protected function headerData(string $paginaAttiva): array
    {
        return [
            'pagina_attiva'      => $paginaAttiva,
            'carrello_conteggio' => (new CartService())->conteggio(),
            'utente_loggato'     => Auth::check() ? '1' : '',
            'nome_utente'        => Auth::check() ? Auth::user()['nome'] : '',
        ];
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    protected function input(string $key, $default = null)
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Genera/verifica un token CSRF per i form POST.
     */
    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrf(): void
    {
        $submitted = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
            $this->flash('errore', 'Sessione scaduta o pagina non aggiornata. Riprova.');
            $back = $_SERVER['HTTP_REFERER'] ?? '/';
            header('Location: ' . $back);
            exit;
        }
    }

    protected function flash(string $tipo, string $messaggio): void
    {
        $_SESSION['flash'][] = ['tipo' => $tipo, 'messaggio' => $messaggio];
    }
}
