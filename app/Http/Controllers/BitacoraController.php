<?php

namespace App\Http\Controllers;

use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BitacoraController extends Controller
{
    protected BitacoraService $bitacoraService;

    public function __construct(BitacoraService $bitacoraService)
    {
        $this->bitacoraService = $bitacoraService;
    }

    /**
     * Display a listing of system log entries.
     * Supports tab=recursos for ranking view,
     * ranking_desde/ranking_hasta for date filtering,
     * and ranking_detalle_menu_id for drill-down modal.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['buscar', 'estado', 'usuario', 'start_date', 'end_date', 'tab']);
        $logs = $this->bitacoraService->listar($filters);

        $props = [
            'logs' => $logs,
            'filters' => $filters,
        ];

        // Ranking resources tab
        $tab = $request->input('tab');
        $rankingDesde = $request->input('ranking_desde');
        $rankingHasta = $request->input('ranking_hasta');

        if ($tab === 'recursos') {
            $props['recursos'] = $this->bitacoraService->recursosMasAccedidos($rankingDesde, $rankingHasta, 10);
            $props['tab'] = 'recursos';
        }

        // Drill-down detail for a specific resource
        $detalleMenuId = $request->input('ranking_detalle_menu_id');
        if ($detalleMenuId) {
            $detalle = $this->bitacoraService->detalleRecurso(
                (int) $detalleMenuId,
                $rankingDesde,
                $rankingHasta
            );

            if ($detalle !== null) {
                $props['detalleRecurso'] = $detalle;
            }
        }

        // Validate desde <= hasta when both present
        if ($rankingDesde && $rankingHasta) {
            try {
                $desdeDate = \Carbon\Carbon::parse($rankingDesde);
                $hastaDate = \Carbon\Carbon::parse($rankingHasta);
                if ($desdeDate->greaterThan($hastaDate)) {
                    return redirect()->route('bitacora.index')
                        ->withErrors(['ranking_desde' => 'La fecha \'desde\' no puede ser posterior a la fecha \'hasta\'.']);
                }
            } catch (\Exception $e) {
                // Invalid date format — ignore, will be handled by the query method
            }
        }

        return Inertia::render('Bitacora/Index', $props);
    }
}
