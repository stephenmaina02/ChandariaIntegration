<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('command:pushnewcustomerstosfa')->everyThreeMinutes()->withoutOverlapping();
Schedule::command('command:pushnewproducttosfa')->everyFiveMinutes()->withoutOverlapping();
// Schedule::command('command:pushdeliverytosfa')->everyTwoMinutes()->withoutOverlapping();
Schedule::command('command:pushorderstatustosfa')->everyMinute()->withoutOverlapping();
// Schedule::command('command:getdeliverydetailsfromsage')->everyTwoMinutes()->withoutOverlapping();
Schedule::command('command:getproductsfromsage')->everyTenMinutes()->withoutOverlapping();
Schedule::command('command:getcustomersfromsage')->everyTenMinutes()->withoutOverlapping();
// Schedule::command('command:failedordersageresync')->hourly()->withoutOverlapping();
// Schedule::command('command:pushpromotiontosfa')->everyFifteenMinutes()->withoutOverlapping();
// Schedule::command('command:getpromotiontosfa')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('command:pushorderresponsetosfa')->everyTwoMinutes()->withoutOverlapping();
Schedule::command('command:getpricelistsfromsage')->hourly()->withoutOverlapping();
Schedule::command('command:truncate_pricelist')->dailyAt('00:17')->withoutOverlapping();
Schedule::command('command:getorderstatusfromsage')->everyThirtyMinutes()->withoutOverlapping();
Schedule::command('command:synccustomerdiscounts')->everyFiveMinutes()->withoutOverlapping();
