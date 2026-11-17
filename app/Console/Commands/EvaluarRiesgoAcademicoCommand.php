<?php

namespace App\Console\Commands;

use App\Services\RiesgoPredictivoService;
use Illuminate\Console\Command;

class EvaluarRiesgoAcademicoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scala:evaluar-riesgo
                            {--grupo= : ID opcional del grupo a evaluar}
                            {--origen=N8N_AUTO : Origen del análisis (MOTOR_LOCAL, N8N_AUTO, ML_API)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta la evaluación predictiva de riesgo académico y deserción sobre las inscripciones (HU19).';

    /**
     * Execute the console command.
     */
    public function handle(RiesgoPredictivoService $service): int
    {
        $grupoId = $this->option('grupo') ? (int) $this->option('grupo') : null;
        $origen = (string) ($this->option('origen') ?: 'N8N_AUTO');

        $this->info('Iniciando análisis predictivo de riesgo académico SCALA...');

        $total = $service->analizarAcademia($grupoId, null, $origen);

        $this->info("Análisis finalizado exitosamente. Inscripciones evaluadas: {$total}.");

        return self::SUCCESS;
    }
}

