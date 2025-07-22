<?php

namespace App\Helpers;

class DataTablesResponse
{
    /**
     * Output the data in DataTable's format.
     */
    public static function output(DataTablesRequest $request,
        array $data, int $totalResults, int $totalFiltered) : array
    {
        return [
            'draw' => $request->draw(),
            'recordsTotal' => $totalResults,
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ];
    }
}
