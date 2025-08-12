<?php

namespace App\Console\Commands;

use App\Http\Controllers\ProductController;
use Illuminate\Console\Command;

class GetProductsFromSage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:getproductsfromsage';

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
        ProductController::getProducts();
        $this->info('Get Product Command Ran successfully!');
    }
}
