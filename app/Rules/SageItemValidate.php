<?php

namespace App\Rules;

use App\Models\StkItem;
use Illuminate\Contracts\Validation\Rule;

class SageItemValidate implements Rule
{
    private $itemcode;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($itemcode)
    {
        $this->itemcode=$itemcode;
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
        $value= StkItem::where('Code', $this->itemcode)->first();
        return count($value)>1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
