<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        //
    }
    protected function invalidJson($request, ValidationException $exception)
    {
        if (array_key_exists('transaction_id', $exception->errors())) {
            $data = $exception->errors()['transaction_id'];
            return response()->json(
                json_decode($data[0], true),
                200
            ); //parent method return 422
        }
        if (array_key_exists('customer_code', $exception->errors())) {
            $data = $exception->errors()['customer_code'];
            return response()->json(
                json_decode($data[0], true),
                200
            ); //pa//parent method return 422
        }
    }
}
