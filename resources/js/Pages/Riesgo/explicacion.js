// El signo es cambio en puntos porcentuales; impacto es solo magnitud relativa.
export function tieneAporte(factor) {
  return factor?.metodo === 'TREE_SHAP'
    && typeof factor.contribucion_pp === 'number'
    && Number.isFinite(factor.contribucion_pp);
}

export function textoAporte(factor) {
  if (!tieneAporte(factor)) {
    return `${factor?.impacto ?? factor?.peso ?? 0}% peso heurístico`;
  }
  const value = factor.contribucion_pp;
  if (Math.abs(value) < 0.005) return 'Sin aporte relevante';
  const label = value > 0 ? 'Eleva' : 'Reduce';
  return `${label} ${Math.abs(value).toLocaleString('es', {
    minimumFractionDigits: 2, maximumFractionDigits: 2,
  })} puntos porcentuales`;
}

export function clasesAporte(factor) {
  if (tieneAporte(factor)) {
    if (factor.contribucion_pp >= 0.005) return 'bg-rose-50 text-rose-700';
    if (factor.contribucion_pp <= -0.005) return 'bg-emerald-50 text-emerald-700';
    return 'bg-gray-100 text-gray-600';
  }
  return 'bg-amber-50 text-amber-700';
}

export function clasesBarraAporte(factor) {
  if (tieneAporte(factor)) {
    if (factor.contribucion_pp >= 0.005) return 'bg-rose-500';
    if (factor.contribucion_pp <= -0.005) return 'bg-emerald-500';
    return 'bg-gray-300';
  }
  return 'bg-amber-500';
}

export function aclaracionFactor(factor) {
  if (!tieneAporte(factor) || factor.contribucion_pp >= -0.005) return '';
  const value = Number(factor.valor_numerico);
  let advertencia = '';
  if (factor.variable === 'faltas_injustificadas' && value > 0) advertencia = 'Una falta sigue siendo una ausencia que requiere seguimiento.';
  if (factor.variable === 'tendencia_notas' && value < 0) advertencia = 'La caída de notas sigue siendo una señal de atención académica.';
  if (factor.variable === 'promedio_calificaciones' && value < 70) advertencia = 'Este promedio sigue por debajo de la zona favorable de 70 puntos.';
  if (factor.variable === 'porcentaje_asistencia' && value < 75) advertencia = 'Esta asistencia sigue siendo baja según los criterios institucionales.';
  if (factor.variable === 'atrasos' && value > 0) advertencia = 'Los atrasos siguen requiriendo seguimiento de puntualidad.';
  if (factor.variable === 'observaciones_negativas' && value > 0) advertencia = 'Estas observaciones siguen requiriendo revisión pedagógica.';
  return advertencia ? advertencia + ' El aporte reduce la estimación solo frente a la referencia del modelo sintético; no significa que este indicador sea favorable ni que mejorarlo aumente el riesgo.' : '';
}
