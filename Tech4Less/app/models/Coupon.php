<?php

class Coupon
{
    /**
     * Ritorna il coupon se il codice è valido e utilizzabile oggi, altrimenti null.
     * Non incrementa gli utilizzi: quello lo fa OrderService solo a ordine confermato.
     */
    public function trovaValido(string $codice): ?array
    {
        $stmt = Database::query(
            'SELECT * FROM coupons
             WHERE codice = ? AND attivo = 1
               AND CURDATE() BETWEEN data_inizio AND data_fine
               AND (utilizzo_massimo IS NULL OR utilizzi_correnti < utilizzo_massimo)
             LIMIT 1',
            [$codice]
        );
        return $stmt->fetch() ?: null;
    }

    public function incrementaUtilizzo(int $couponId): void
    {
        Database::query('UPDATE coupons SET utilizzi_correnti = utilizzi_correnti + 1 WHERE id = ?', [$couponId]);
    }

    public function calcolaSconto(array $coupon, float $subtotale): float
    {
        if ($coupon['tipo'] === 'percentuale') {
            return round($subtotale * ((float) $coupon['valore'] / 100), 2);
        }

        return min((float) $coupon['valore'], $subtotale);
    }
}
