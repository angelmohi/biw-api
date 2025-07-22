<?php

namespace App\Models;

class TransactionType extends Model
{
    /**
     * Fichajes (DB value)
     *
     * @var int
     */
    const TRANSFER = 1;

    /**
     * Mercado de fichajes (DB value)
     *
     * @var int
     */
    const MARKET = 2;

    /**
     * Fin de jornada (DB value)
     *
     * @var int
     */
    const ROUND_FINISHED = 3;
}
