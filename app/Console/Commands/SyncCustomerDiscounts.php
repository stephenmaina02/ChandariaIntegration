<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CustomerController;

class SyncCustomerDiscounts extends Command
{
    protected $signature = 'command:synccustomerdiscounts
                            {--account= : Limit the run to a single Sage account}
                            {--push-only : Skip the Sage comparison and only push pending discount updates}
                            {--limit=100 : Maximum number of customers to push to SFA in this run}';

    protected $description = 'Compare existing customer discounts against Sage and push the changed ones to SFA';

    public function handle()
    {
        if (!$this->option('push-only')) {
            $changed = CustomerController::syncDiscountsFromSage($this->option('account'));
            $this->info($changed . ' customer discount(s) changed in Sage.');
        }

        $pushed = CustomerController::pushDiscountUpdatesToSfa((int) $this->option('limit'));
        $this->info($pushed . ' customer discount(s) pushed to SFA.');
    }
}
