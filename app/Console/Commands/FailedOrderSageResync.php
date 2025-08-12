<?php

namespace App\Console\Commands;

use App\Http\Controllers\OrderControllerRefactored;
use Illuminate\Console\Command;

class FailedOrderSageResync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:failedordersageresync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        OrderControllerRefactored::sageResync();
        $this->info('Order Resync Ran successfully!');
    }
}
