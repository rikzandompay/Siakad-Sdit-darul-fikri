<?php

namespace App\Database;

use Illuminate\Database\PostgresConnection;

/**
 * PHP 8.4+ (pdo_pgsql) mengirim bind integer sebagai tipe integer, sehingga Postgres menolak
 * menulisnya ke kolom boolean ("column is of type boolean but expression is of type integer").
 * Laravel mengubah bool menjadi int; di sini bool dikirim sebagai 'true'/'false' (teks tanpa tipe)
 * yang diterima kolom boolean di semua versi PHP.
 */
class PostgresKoneksi extends PostgresConnection
{
    public function prepareBindings(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_bool($value)) {
                $bindings[$key] = $value ? 'true' : 'false';
            }
        }

        return parent::prepareBindings($bindings);
    }
}
