<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run --only-db')->daily()->at('01:30');
Schedule::command('backup:run')->weeklyOn(0, '02:00');
Schedule::command('backup:monitor')->daily()->at('03:00');
Schedule::command('model:prune')->daily();
