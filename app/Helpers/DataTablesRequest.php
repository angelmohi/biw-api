<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class DataTablesRequest
{
    /**
     * Draw.
     *
     * @var int
     */
    protected $draw = 1;
    
    /**
     * The starting index.
     *
     * @var int
     */
    protected $start = 0;
    
    /**
     * The number of items per page.
     *
     * @var int
     */
    protected $length = 10;
    
    /**
     * The term to search.
     *
     * @var string
     */
    protected $search = null;

    /**
     * The term to search for a specific column.
     * 
     * @var array
     */
    protected $searchColumns = [];
    
    /**
     * The columns order.
     *
     * @var array
     */
    protected $order = [];
    
    /**
     * Constructor.
     */
    public function __construct(Request $request)
    {
        // Draw
        if ($request->draw) {
            $this->draw = intval($request->draw);
        }
        
        // Row range
        if ($request->start) {
            $this->start = intval($request->start);
        }
        if ($request->length) {
            $this->length = intval($request->length);
        }
        
        // Search
        if ($request->search && is_array($request->search) && isset($request->search['value'])) {
            $this->search = $request->search['value'];
        }

        // Search Columns
        if ($request->columns && is_array($request->columns)) {
            foreach ($request->columns as $column) {
                if (isset($column['search']) && is_array($column['search']) && isset($column['search']['value'])) {
                    $this->searchColumns[] = $column['search']['value'];
                } else {
                    $this->searchColumns[] = null;
                }
            }
        }
        
        // Order
        if ($request->order && is_array($request->order)) {
            foreach ($request->order as $orderColumn) {
                if (isset($orderColumn['column']) && isset($orderColumn['dir'])) {
                    $this->order[intval($orderColumn['column'])] = $orderColumn['dir'] == 'desc' ? 'desc' : 'asc';
                }
            }
        }
    }
    
    /**
     * Get the draw.
     */
    public function draw() : int
    {
        return $this->draw;
    }
    
    /**
     * Get the starting index.
     */
    public function start() : int
    {
        return $this->start;
    }
    
    /**
     * Get the number of items per page.
     */
    public function length() : int
    {
        return $this->length;
    }
    
    /**
     * Get the term to search.
     */
    public function search() : string|null
    {
        return $this->search;
    }

    /**
     * Get the term to search for a specific column.
     */
    public function searchColumns() : array
    {
        return $this->searchColumns;
    }
    
    /**
     * Get the columns order.
     */
    public function order() : array
    {
        return $this->order;
    }
}
