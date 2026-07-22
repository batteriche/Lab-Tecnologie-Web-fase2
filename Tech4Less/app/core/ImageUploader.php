<?php

/**
 * Gestisce l'upload di un'immagine prodotto: valida tipo ed estensione,
 * genera un nome file sicuro (mai il nome originale, per evitare path traversal
 * o collisioni), sposta il file in public/assets/img/.
 */
class ImageUploader
{
    private const ESTENSIONI_AMMESSE = ['jpg', 'jpeg', 'png', 'webp'];
    private const MIME_AMMESSI = ['image/jpeg', 'image/png', 'image/webp'];
    private const DIMENSIONE_MASSIMA = 5 * 1024 * 1024; // 5 MB

    /**
     * @return string|null Nome file salvato (da mettere in product_images.percorso,
     *                     coerente con la convenzione già in uso nel seed: solo il
     *                     nome, non un percorso completo), oppure null se non è stato
     *                     caricato alcun file (campo vuoto/opzionale).
     * @throws InvalidArgumentException se il file è presente ma non valido
     */
    public static function gestisci(array $file): ?string
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Errore durante il caricamento del file.');
        }

        if ($file['size'] > self::DIMENSIONE_MASSIMA) {
            throw new InvalidArgumentException('Il file supera i 5MB consentiti.');
        }

        $estensione = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($estensione, self::ESTENSIONI_AMMESSE, true)) {
            throw new InvalidArgumentException('Formato non supportato (usa JPG, PNG o WEBP).');
        }

        $mimeReale = mime_content_type($file['tmp_name']);
        if (!in_array($mimeReale, self::MIME_AMMESSI, true)) {
            throw new InvalidArgumentException("Il contenuto del file non corrisponde a un'immagine valida.");
        }

        $nomeFile = bin2hex(random_bytes(8)) . '.' . $estensione;
        $cartella = APP_ROOT . '/public/assets/img/';

        if (!is_dir($cartella)) {
            mkdir($cartella, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $cartella . $nomeFile)) {
            throw new InvalidArgumentException('Impossibile salvare il file caricato.');
        }

        return $nomeFile;
    }
}
