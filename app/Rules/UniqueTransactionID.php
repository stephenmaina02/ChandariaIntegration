<?php

namespace App\Rules;

use Carbon\Carbon;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Contracts\Validation\Rule;
use App\Models\ItemList;

class UniqueTransactionID implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // $trans_id = '';
        // $client_code = strtoupper(request()->input('client_code'));
        // if ($client_code == 'CADBURY') {
        //     $value = 'CD' . request()->input('transaction_id');
        // } else if ($client_code == 'PG') {
        //     $value = 'PG' . request()->input('transaction_id');
        // } else {
        //     $value = request()->input('transaction_id');
        // }
        // $order = Order::where('transaction_id', $value)->get();

        // return count($order) < 1;
		$value = 'SATCHA' . request()->input('transaction_id');
        $order=Order::where('transaction_id', $value)->where('status',0)->first();
        if(!is_null($order)){
            $order->delete();
            ItemList::where('transaction_id', $value)->delete();
        }
        $transactionExist = Order::where('transaction_id', $value)->get();
        return count($transactionExist) < 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return json_encode([
            'transaction_reference' => null,
            'transaction_number' => null,
            'message' => 'Transaction id already taken. Contact Pevium System if tracker not sent',
            'code' => -1,
            'transaction_id' =>request()->input('transaction_id'),
            'createdate' => Carbon::now()
        ]);
    }
}
