<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
// Polling cada 15 segundos para pruebas (QR de prueba expira en ~2-3 min)
Schedule::command('pagofacil:verificar-pendientes')->everyFifteenSeconds();

