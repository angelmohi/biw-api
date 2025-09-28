<?php

namespace App\Models;

use App\Helpers\DataTablesRequest;
use App\Helpers\DataTablesResponse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the type of the transaction
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    /**
     * Get the from user of the transaction
     */
    public function userFrom(): BelongsTo
    {
        return $this->belongsTo(BiwengerUser::class, 'from_user_id');
    }

    /**
     * Get the to user of the transaction
     */
    public function userTo(): BelongsTo
    {
        return $this->belongsTo(BiwengerUser::class, 'to_user_id');
    }

    /**
     * Get the player price on the transaction date
     */
    public function getPlayerPriceOnDate()
    {
        if (!$this->player_id || !$this->date) {
            return null;
        }

        $priceRecord = PlayerPriceHistory::where('biwenger_player_id', $this->player_id)
            ->where('record_date', $this->date->format('Y-m-d'))
            ->first();

        return $priceRecord;
    }

    /**
     * Get formatted player price on the transaction date
     */
    public function getFormattedPlayerPrice()
    {
        $priceRecord = $this->getPlayerPriceOnDate();
        
        if (!$priceRecord) {
            return null;
        }

        return [
            'price' => $priceRecord->getPriceInEuros(),
            'price_increment' => $priceRecord->getPriceIncrementInEuros(),
            'formatted_price' => number_format($priceRecord->getPriceInEuros(), 0, ',', '.') . '€',
            'formatted_increment' => $priceRecord->price_increment >= 0 
                ? '+' . number_format($priceRecord->getPriceIncrementInEuros(), 0, ',', '.') . '€'
                : number_format($priceRecord->getPriceIncrementInEuros(), 0, ',', '.') . '€'
        ];
    }

    /**
     * Paginated list of transactions
     */
    public static function listing(DataTablesRequest $request) : array
    {
        $query = DB::table('transaction')
            ->select([
                'transaction_type.name as type_name',
                'transaction.description as description',
                'transaction.amount as amount',
                'transaction.player_name as player_name',
                'from_user.name as user_from_name',
                'to_user.name as user_to_name',
                'transaction.date as date',
                'transaction.id'
            ])
            ->leftJoin('transaction_type', 'transaction_type.id', '=', 'transaction.type_id')
            ->leftJoin('biwenger_user as from_user', 'from_user.id', '=', 'transaction.from_user_id')
            ->leftJoin('biwenger_user as to_user', 'to_user.id', '=', 'transaction.to_user_id')
            ->offset($request->start())
            ->limit($request->length());

        if ($term = $request->search()) {
            $query->where(function ($subQuery) use ($term) {
                $subQuery
                    ->where('transaction_type.name', 'like', "%$term%")
                    ->orWhere('transaction.description', 'like', "%$term%")
                    ->orWhere('transaction.amount', 'like', "%$term%")
                    ->orWhere('transaction.player_name', 'like', "%$term%")
                    ->orWhere('from_user.name', 'like', "%$term%")
                    ->orWhere('to_user.name', 'like', "%$term%")
                    ->orWhereRaw("DATE_FORMAT(transaction.date, '%d/%m/%Y %H:%i') like ?", ["%$term%"]);
            });
        }

        foreach ($request->order() as $columnIndex => $orderDir) {
            switch ($columnIndex) {
                case 0 :
                    $query->orderBy('transaction_type.name', $orderDir);
                    break;
				case 1 :
                    $query->orderBy('transaction.description', $orderDir);
                    break;
                case 2 :
                    $query->orderBy('transaction.amount', $orderDir);
                    break;
                case 3 :
                    $query->orderBy('transaction.player_name', $orderDir);
                    break;
                case 4 :
                    $query->orderBy('from_user.name', $orderDir);
                    break;
                case 5 :
                    $query->orderBy('to_user.name', $orderDir);
                    break;
                case 6 :
                    $query->orderBy('transaction.date', $orderDir);
                    break;
            }
        }

        if (empty($request->order())) {
            $query->orderBy('transaction.date', 'desc');
        }

        // Results
        $collection = $query->get();

        $totalFilteredQuery = DB::table('transaction')
            ->selectRaw('COUNT(*) as total')
            ->leftJoin('transaction_type', 'transaction_type.id', '=', 'transaction.type_id')
            ->leftJoin('user as from_user', 'from_user.id', '=', 'transaction.from_user_id')
            ->leftJoin('user as to_user', 'to_user.id', '=', 'transaction.to_user_id');
        if ($term) {
            $totalFilteredQuery->where(function ($subQuery) use ($term) {
                $subQuery
                    ->where('transaction_type.name', 'like', "%$term%")
                    ->orWhere('transaction.description', 'like', "%$term%")
                    ->orWhere('transaction.amount', 'like', "%$term%")
                    ->orWhere('transaction.player_name', 'like', "%$term%")
                    ->orWhere('from_user.name', 'like', "%$term%")
                    ->orWhere('to_user.name', 'like', "%$term%")
                    ->orWhereRaw("DATE_FORMAT(transaction.date, '%d/%m/%Y %H:%i') like ?", ["%$term%"]);
            });
        }
        $totalFiltered = $totalFilteredQuery->value('total');
        
        $totalResults = DB::table('transaction')->count();

        $data = [];
        foreach ($collection as $transaction) {
            $row = [
                // Columns
                $transaction->type_name,
                $transaction->description ?? '-',
                number_format($transaction->amount, 0, '', '.') . '€',
                $transaction->player_name ?? '-',
                $transaction->user_from_name ?? '-',
                $transaction->user_to_name ?? '-',
                Carbon::parse($transaction->date)->format('d/m/Y H:i'),

                // Row data
                'DT_RowData' => [
                    'id' => $transaction->id,
                ],
            ];

            $data[] = $row;
        }

        return DataTablesResponse::output($request, $data, $totalResults, $totalFiltered);
    }
}
