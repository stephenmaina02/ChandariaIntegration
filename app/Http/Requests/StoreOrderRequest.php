<?php

namespace App\Http\Requests;

use App\Rules\CustomerExistCheck;
use App\Rules\UniqueTransactionID;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required',
            'transaction_id' => ['required', new UniqueTransactionID()],
            "transaction_type" => "required",
            "customer_code" => ['required', new CustomerExistCheck()],
            "item_list" => "required",
            "transaction_date" => "required"
        ];
    }
}
