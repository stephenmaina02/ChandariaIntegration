<?php

namespace App\Service;

class SDK{
    public function sdkConnect()
    {
        ini_set('display_errors', 1);
        ini_set("com.allow_dcom","true");
//We are using this function to log the progress of our simple procedure below

try
{
    com_load_typelib("Pastel.Evolution");
    //Create the COM Helper object
    $sdkHelper = new \COM("Pastel.Evolution") or die ("Could not initialise MS Word object.");;
    // new \COM("word.application") or die ("Could not initialise MS Word object.");
    \Log::warning("Loaded ComHelper, version {$sdkHelper->AssemblyVersion}");
    //Initialise
    $sdkHelper->CreateCommonDBConnection("uid=ifraan;pwd=Mashaallah786;Initial Catalog=EvolutionCommon;server=(CHANDARIA-BI-SR\SKYWALKER)");
    \Log::warning("Connected to common");
    $sdkHelper->SetLicense("DEMO", "67681607597478");
    \Log::warning("License set");
    $sdkHelper->CreateConnection("uid=ifraan;pwd=Mashaallah786;Initial Catalog=50008;server=(CHANDARIA-BI-SR\SKYWALKER)");
    \Log::warning("Connected to database");
    if ($sdkHelper->CurrentEvolutionDatabaseVersion != $sdkHelper->CompatibleEvolutionDatabaseVersion)
    {
        \Log::warning("Warning: the current database version is {$sdkHelper->CurrentEvolutionDatabaseVersion} while this version of the SDK is intended for version {$sdkHelper->CompatibleEvolutionDatabaseVersion}");
    }
 
    /* This is how you would create a new customer account:
    $newAccount = new COM("Pastel.Evolution.Customer");
    $newAccount->Code = "PHPTest";
    \Log::warning("Saving...";
    $newAccount->Save();
    \Log::warning("New account ID: {$newAccount->ID}";
    */
    //Initialise an existing account, using it's code
    // $cashAccount = $sdkHelper->GetARAccount("CASH");
    // //Create the new sales order object
    // $salesOrder = new COM("Pastel.Evolution.SalesOrder");
    // \Log::warning("Line items: {$salesOrder->Detail->Count}");
    // \Log::warning("New Sales Order Total: {$salesOrder->TotalExcl}");
    // //Specify the order's customer account
    // $salesOrder->Customer = $cashAccount;
    // //Retrieve an existing inventory item using its code.
    // $stockItem = $sdkHelper->GetStockItem("ABC");
    // \Log::warning("Fetched item");
    // \Log::warning("Qty on hand: {$stockItem->QtyOnHand}");
    // \Log::warning("Qty free: {$stockItem->QtyFree}");
    // //Create an order line object and populate it
    // $orderDetail = new COM("Pastel.Evolution.OrderDetail");
    // $orderDetail->InventoryItem = $stockItem;
    // $orderDetail->Quantity = 5;
    // $orderDetail->UnitSellingPrice = 50;
    // //Add the order line to the order document
    // $salesOrder->Detail->Add($orderDetail);
    // \Log::warning("Line items: {$salesOrder->Detail->Count}");
    // \Log::warning("New sales order total (incl.): {$salesOrder->TotalIncl}");
    // //Completely process the sales order and store the generated invoice number
    // $reference = $salesOrder->Complete();
 
    // /*Should you wish to pass in your own invoice number, do this instead:
    // foreach ($salesOrder->Detail as $det)
    // {
    //     \Log::warning($det->Quantity);
    //     $det->ToProcess = $det->Quantity;
    // }
    // $reference = $sdkHelper->ProcessDocumentWithCustomReference($salesOrder, "553343453");
    // */
 
    // \Log::warning("New invoice number: {$reference}");
    // \Log::warning("Debtor transaction total: {$salesOrder->LedgerTransaction->Debit}");
 
 
}
catch (Exception $ex)
{
    \Log::warning($ex->GetMessage());
    //prn($ex->getCode());
    //prn($ex->getTraceAsString());
}
 
    }
}