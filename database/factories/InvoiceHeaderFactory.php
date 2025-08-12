<?php

namespace Database\Factories;

use App\Models\InvoiceHeader;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceHeaderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InvoiceHeader::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'DocType'=> $this->faker->randomDigit,
            'Reference'=> $this->faker->word,
            'TxDate'=> $this->faker->date,
            'CustomerID'=>  $this->faker->randomNumber($nbDigits = NULL, $strict = false),
            'CustomerCode'=> $this->faker->word,
            'BranchID' =>$this->faker->randomDigit,
            'BranchCode'=> $this->faker->word,
        ];
    }
}
