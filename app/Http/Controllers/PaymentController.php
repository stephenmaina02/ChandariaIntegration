<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function paymentData()
    {
        // to return json here
        request()->validate([
            'payment_id' => 'required|unique:payments,payment_id',
            'amount' => 'required'
        ]);
        return [
            "payment_id" => request()->input('payment_id'),
            "transaction_id" =>  request()->input('transaction_id'),
            "user_code" =>  request()->input('user_code'),
            "notes" =>  request()->input('notes'),
            "customer_code" =>  request()->input('customer_code'),
            "payment_reference" =>  request()->input('payment_reference'),
            "payment_date" =>  request()->input('payment_date'),
            "amount" =>  request()->input('amount'),
            "payment_mode" =>  request()->input('payment_mode'),
            "maturity_date" =>  request()->input('maturity_date'),
            'erp_reference' => request()->input('erp_reference'),
            'client_code' => request()->input('client_code'),
            'invoices' => json_encode(request()->input('invoices')),
            'bank_code' => request()->input('bank_code'),
            'status' => 0,
        ];
    }
    public function saveToStaging()
    {
        $payment_data = $this->paymentData();
        $isPaymentCreated = Payment::create($payment_data);
        return json_encode(['message'=>'Saved']);
        // if ($isPaymentCreated) {
        //     $savedToSage = $this->saveToSage($payment_data);

        //     if ($savedToSage) {
        //         return ["docNum" => $payment_data['payment_id'], "message" => "SUCCESS", "code" => 1, "payment_id" => $payment_data['payment_id'], "createdate" => Carbon::now()];
        //     } else

        //         return ["docNum" => $payment_data['payment_id'], "message" => "Failed to Save on Sage", "code" => -1, "payment_id" => $payment_data['payment_id'], "createdate" => Carbon::now()];
        // }
        // else
        // return ["docNum" => $payment_data['payment_id'], "message" => "Unable to Create on Staging table", "code" => -1, "payment_id" => $payment_data['payment_id'], "createdate" => Carbon::now()];
    }

    public function saveToSage($data)
    {
        $date = $data['payment_date'];
        $batchId = 1;
        $accountId = $this->customerDet($data['customer_code']);
        $iTrcode = 13;
        $iGlContra = 88;
        $postDated = 0;
        $cRef = $data['payment_reference'];
        $description = $data['notes'];
        $iTaxTypeId = 0;
        $fExchangeRate = 1;

        DB::beginTransaction();
        $transaction = [];
        foreach (json_decode($data['invoices']) as $inv) {
            $trans = DB::insert(
                'INSERT INTO ' . env("SAGE_HOST_DB_NAME") . '[_etblARAPBatchLines] ([iBatchID],[dTxDate],[iAccountID],[iAccountCurrencyID],[iTrCodeID],[iGLContraID]
       ,[bPostDated],[cReference],[cDescription], cOrderNumber,[fAmountExcl],[iTaxTypeID],[fAmountIncl],[fExchangeRate] ,[fAmountExclForeign],[fAmountInclForeign]) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$batchId, $date, $accountId, 0, $iTrcode, $iGlContra, $postDated, $cRef, $description,$inv->erp_reference, $inv->amount, $iTaxTypeId, $inv->amount, $fExchangeRate, $inv->amount, $inv->amount ]
            );
            array_push($transaction,$trans);
        }
        if (in_array(false, $transaction)) {
            DB::rollBack();
            return false;
        } else {
            DB::commit();
            // To check if we can use this as docnum
            $recordId = DB::getPdo()->lastInsertId();
            Log::info("Batch record $recordId");
            return true;
        }
    }
    public function customerDet($customer_code)
    {
        $customer= DB::selectOne("SELECT DCLink, Account FROM ". env('SAGE_HOST_DB_NAME') . "[_bvARAccountsFull] WHERE Account='$customer_code'");
        if(!is_null($customer)){
            return $customer->DCLink;
        }
        else{
            Log::error("Customer $customer_code not found for the payment");
            die("Customer not found");
        }
    }

    // check if batch exists and if doesn't exists create
}
