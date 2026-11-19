<?php

namespace App\Exceptions;

use LogicException;

class RiwayatTidakBisaDiubah extends LogicException
{
    public function __construct()
    {
        parent::__construct('Riwayat aktivitas tidak bisa diubah atau dihapus.');
    }
}
