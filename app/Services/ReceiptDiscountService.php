<?php

namespace App\Services;

use InvalidArgumentException;

class ReceiptDiscountService
{
    private const PERCENT_SCALE = 10000;
    private const PERCENT_DENOMINATOR = 100 * self::PERCENT_SCALE;

    public function preview(array $input): array
    {
        $totalAwalCents = $this->toCents($input['total_awal'] ?? null, 'total_awal');
        $pajakCents = $this->toCents($input['pajak'] ?? 0, 'pajak');

        $diskonNominal = $input['diskon_nominal'] ?? null;
        $diskonPersen = $input['diskon_persen'] ?? null;

        $diskonNominalCents = $diskonNominal === null ? null : $this->toCents($diskonNominal, 'diskon_nominal');
        $diskonPersenScaled = $diskonPersen === null ? null : $this->toPercentScaled($diskonPersen, 'diskon_persen');

        $pembulatan = is_array(($input['pembulatan'] ?? null)) ? $input['pembulatan'] : [];
        $mode = $this->normalizeMode($pembulatan['mode'] ?? 'none');
        $unit = (int) ($pembulatan['unit'] ?? 0);
        if ($unit <= 0) {
            $unit = 1;
        }

        $result = $this->calculate(
            baseCents: $totalAwalCents,
            taxCents: $pajakCents,
            discountAmountCents: $diskonNominalCents,
            discountPercentScaled: $diskonPersenScaled,
            roundingMode: $mode,
            roundingUnitRupiah: $unit,
        );

        return [
            'total_awal' => $this->fromCents($result['base_cents']),
            'diskon_nominal' => $this->fromCents($result['discount_cents']),
            'diskon_persen' => $this->fromPercentScaled($result['discount_percent_scaled']),
            'total_akhir' => $this->fromCents($result['total_final_cents']),
            'pembulatan' => [
                'applied' => (bool) $result['rounding_applied'],
                'mode' => $result['rounding_mode'],
                'unit' => (int) $result['rounding_unit_rupiah'],
                'total_sebelum' => $this->fromCents($result['total_before_rounding_cents']),
                'total_setelah' => $this->fromCents($result['total_final_cents']),
                'delta_total' => $this->fromCents($result['rounding_delta_total_cents']),
                'diskon_delta' => $this->fromCents($result['rounding_delta_discount_cents']),
            ],
        ];
    }

    public function calculate(
        int $baseCents,
        int $taxCents = 0,
        ?int $discountAmountCents = null,
        ?int $discountPercentScaled = null,
        string $roundingMode = 'none',
        int $roundingUnitRupiah = 1,
    ): array {
        if ($baseCents < 0) {
            throw new InvalidArgumentException('total_awal tidak valid');
        }
        if ($taxCents < 0) {
            throw new InvalidArgumentException('pajak tidak valid');
        }
        if ($discountAmountCents === null && $discountPercentScaled === null) {
            throw new InvalidArgumentException('diskon_nominal atau diskon_persen wajib diisi');
        }

        if ($discountAmountCents !== null) {
            if ($discountAmountCents < 0) {
                throw new InvalidArgumentException('diskon_nominal tidak valid');
            }
            if ($discountAmountCents > $baseCents) {
                throw new InvalidArgumentException('diskon_nominal melebihi total_awal');
            }
            $discountCents = $discountAmountCents;
            $discountPercentScaledComputed = $baseCents === 0
                ? 0
                : (int) $this->divRoundHalfUp($discountCents * self::PERCENT_DENOMINATOR, $baseCents);
        } else {
            if ($discountPercentScaled === null) {
                throw new InvalidArgumentException('diskon_persen tidak valid');
            }
            if ($discountPercentScaled < 0 || $discountPercentScaled > self::PERCENT_DENOMINATOR) {
                throw new InvalidArgumentException('diskon_persen harus di antara 0 dan 100');
            }
            $discountCents = (int) $this->divRoundHalfUp($baseCents * $discountPercentScaled, self::PERCENT_DENOMINATOR);
            if ($discountCents > $baseCents) {
                $discountCents = $baseCents;
            }
            $discountPercentScaledComputed = $discountPercentScaled;
        }

        $totalBeforeRoundingCents = $baseCents - $discountCents + $taxCents;
        if ($totalBeforeRoundingCents < 0) {
            throw new InvalidArgumentException('total akhir tidak boleh negatif');
        }

        $roundingUnitCents = max(1, $roundingUnitRupiah) * 100;
        $roundingMode = $this->normalizeMode($roundingMode);

        $roundingApplied = $roundingMode !== 'none' && $roundingUnitCents > 0;
        $totalFinalCents = $totalBeforeRoundingCents;
        if ($roundingApplied) {
            $totalFinalCents = $this->roundToUnit($totalBeforeRoundingCents, $roundingUnitCents, $roundingMode);
        }

        $roundingDeltaTotalCents = $totalFinalCents - $totalBeforeRoundingCents;
        $roundingDeltaDiscountCents = 0;

        $discountFinalCents = $discountCents;
        if ($roundingApplied && $roundingDeltaTotalCents !== 0) {
            $discountFinalCents = $discountCents - $roundingDeltaTotalCents;
            $roundingDeltaDiscountCents = $discountFinalCents - $discountCents;
            if ($discountFinalCents < 0) {
                throw new InvalidArgumentException('pembulatan menghasilkan diskon negatif');
            }
            if ($discountFinalCents > $baseCents) {
                throw new InvalidArgumentException('pembulatan menghasilkan diskon melebihi total_awal');
            }
        }

        $discountPercentScaledFinal = $baseCents === 0
            ? 0
            : (int) $this->divRoundHalfUp($discountFinalCents * self::PERCENT_DENOMINATOR, $baseCents);

        return [
            'base_cents' => $baseCents,
            'tax_cents' => $taxCents,
            'discount_cents' => $discountFinalCents,
            'discount_percent_scaled' => $discountPercentScaledFinal,
            'total_before_rounding_cents' => $totalBeforeRoundingCents,
            'total_final_cents' => $baseCents - $discountFinalCents + $taxCents,
            'rounding_applied' => $roundingApplied && $roundingDeltaTotalCents !== 0,
            'rounding_mode' => $roundingMode,
            'rounding_unit_rupiah' => (int) max(1, $roundingUnitRupiah),
            'rounding_delta_total_cents' => $roundingDeltaTotalCents,
            'rounding_delta_discount_cents' => $roundingDeltaDiscountCents,
        ];
    }

    private function normalizeMode(string $mode): string
    {
        $mode = strtolower(trim($mode));
        return in_array($mode, ['none', 'nearest', 'up', 'down'], true) ? $mode : 'none';
    }

    private function roundToUnit(int $valueCents, int $unitCents, string $mode): int
    {
        if ($unitCents <= 0) {
            return $valueCents;
        }
        if ($mode === 'none') {
            return $valueCents;
        }

        $remainder = $valueCents % $unitCents;
        if ($remainder === 0) {
            return $valueCents;
        }

        if ($mode === 'down') {
            return $valueCents - $remainder;
        }
        if ($mode === 'up') {
            return $valueCents + ($unitCents - $remainder);
        }

        $down = $valueCents - $remainder;
        $up = $valueCents + ($unitCents - $remainder);
        $distDown = $valueCents - $down;
        $distUp = $up - $valueCents;

        if ($distUp < $distDown) {
            return $up;
        }
        if ($distDown < $distUp) {
            return $down;
        }
        return $down;
    }

    private function toCents($value, string $field): int
    {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException("{$field} wajib diisi");
        }
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("{$field} harus berupa angka");
        }

        $float = (float) $value;
        $cents = (int) round($float * 100, 0, PHP_ROUND_HALF_UP);
        if ($cents < 0) {
            throw new InvalidArgumentException("{$field} tidak valid");
        }
        return $cents;
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function toPercentScaled($value, string $field): int
    {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException("{$field} wajib diisi");
        }
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("{$field} harus berupa angka");
        }

        $float = (float) $value;
        $scaled = (int) round($float * self::PERCENT_SCALE, 0, PHP_ROUND_HALF_UP);
        return $scaled;
    }

    private function fromPercentScaled(int $scaled): string
    {
        return number_format($scaled / self::PERCENT_SCALE, 4, '.', '');
    }

    private function divRoundHalfUp(int $numerator, int $denominator): int
    {
        if ($denominator === 0) {
            throw new InvalidArgumentException('pembagi tidak boleh nol');
        }
        $sign = ($numerator < 0 xor $denominator < 0) ? -1 : 1;
        $n = abs($numerator);
        $d = abs($denominator);

        $q = intdiv($n, $d);
        $r = $n % $d;
        if ($r * 2 >= $d) {
            $q++;
        }
        return $q * $sign;
    }
}

