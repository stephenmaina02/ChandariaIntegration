<?php

namespace App\Rules;

use App\Models\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class CustomerExistCheck implements Rule
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
        //$customer = Customer::where('account', request()->input('customer_code'))->first();
        $customer= DB::selectOne("select account from vw_customers where account='".request()->input('customer_code')."'");
        return !is_null($customer);

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
            'message' => 'Customer code '. request()->input('customer_code').' does not exist on sage ',
            'code' => -1,
            'transaction_id' =>request()->input('transaction_id'),
            'createdate' => Carbon::now()
        ]);
    }
}
