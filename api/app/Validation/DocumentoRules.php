<?php

namespace App\Validation;

class DocumentoRules
{
    public function documento(string $value, ?string &$error = null): bool
    {
        $digits = preg_replace('/\D+/', '', $value ?? '');
        if ($digits === null) {
            $error = 'Documento invalido.';
            return false;
        }

        if (strlen($digits) === 11) {
            return $this->isValidCpf($digits, $error);
        }

        if (strlen($digits) === 14) {
            return $this->isValidCnpj($digits, $error);
        }

        $error = 'Documento deve ser CPF ou CNPJ valido.';
        return false;
    }

    private function isValidCpf(string $digits, ?string &$error = null): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $digits)) {
            $error = 'CPF invalido.';
            return false;
        }

        $sum = 0;
        for ($i = 0, $weight = 10; $i < 9; $i++, $weight--) {
            $sum += (int) $digits[$i] * $weight;
        }

        $check = ($sum * 10) % 11;
        $check = ($check === 10) ? 0 : $check;

        if ($check !== (int) $digits[9]) {
            $error = 'CPF invalido.';
            return false;
        }

        $sum = 0;
        for ($i = 0, $weight = 11; $i < 10; $i++, $weight--) {
            $sum += (int) $digits[$i] * $weight;
        }

        $check = ($sum * 10) % 11;
        $check = ($check === 10) ? 0 : $check;

        if ($check !== (int) $digits[10]) {
            $error = 'CPF invalido.';
            return false;
        }

        return true;
    }

    private function isValidCnpj(string $digits, ?string &$error = null): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $digits)) {
            $error = 'CNPJ invalido.';
            return false;
        }

        $weightsOne = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits[$i] * $weightsOne[$i];
        }

        $mod = $sum % 11;
        $check = ($mod < 2) ? 0 : 11 - $mod;

        if ($check !== (int) $digits[12]) {
            $error = 'CNPJ invalido.';
            return false;
        }

        $weightsTwo = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $digits[$i] * $weightsTwo[$i];
        }

        $mod = $sum % 11;
        $check = ($mod < 2) ? 0 : 11 - $mod;

        if ($check !== (int) $digits[13]) {
            $error = 'CNPJ invalido.';
            return false;
        }

        return true;
    }
}
