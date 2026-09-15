<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold" :style="{ color: 'var(--color-primary)' }">Gestión de Horarios</h1>
          <p class="text-sm text-gray-500">Administra las disponibilidades de los docentes y asignaciones de aulas para los grupos.</p>
        </div>
        <div class="flex gap-2" v-if="puedeGestionar">
          <button
            @click="abrirNuevoDocente"
            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 transition cursor-pointer"
            title="Nueva Disponibilidad Docente"
          >
            <component :is="Lucide.Plus" class="w-5 h-5" />
          </button>
          <button
            @click="abrirNuevoGrupo"
            class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 transition cursor-pointer"
            title="Nuevo Horario de Grupo"
          >
            <component :is="Lucide.Plus" class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Flash Messages -->
      <div v-if="$page.props.flash?.success" class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.errors?.general" class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        {{ $page.props.errors.general }}
      </div>

      <!-- Main Modals/Forms (Inline simulation) -->
      <div v-if="mostrarFormDocente" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div class="w-full max-w-lg bg-white rounded-xl shadow-lg relative overflow-hidden">
          <CreateDocente
            :docentes="docentes"
            :horario="editandoDocente"
            @success="cerrarFormDocente"
            @cancel="cerrarFormDocente"
          />
        </div>
      </div>

      <div v-if="mostrarFormGrupo" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
        <div class="w-full max-w-lg bg-white rounded-xl shadow-lg relative overflow-hidden">
          <CreateGrupo
            :grupos="grupos"
            :salas="salas"
            :horario="editandoGrupo"
            @success="cerrarFormGrupo"
            @cancel="cerrarFormGrupo"
          />
        </div>
      </div>

      <!-- Filters (Only for Propietario/Secretaria) -->
      <div v-if="puedeGestionar" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex flex-wrap gap-4 items-center">
        <div class="w-full sm:w-auto">
          <label class="block text-xs font-semibold text-gray-500 mb-1">Filtrar por Docente</label>
          <select
            v-model="filtroDocente"
            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
            @change="aplicarFiltros"
          >
            <option value="">Todos los docentes</option>
            <option v-for="d in docentes" :key="d.id" :value="d.id">{{ d.nombre }}</option>
          </select>
        </div>

        <div class="w-full sm:w-auto">
          <label class="block text-xs font-semibold text-gray-500 mb-1">Filtrar por Sala (Aula)</label>
          <select
            v-model="filtroSala"
            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
            @change="aplicarFiltros"
          >
            <option value="">Todas las salas</option>
            <option v-for="s in salas" :key="s.id" :value="s.id">{{ s.nombre }}</option>
          </select>
        </div>

        <div class="w-full sm:w-auto">
          <label class="block text-xs font-semibold text-gray-500 mb-1">Filtrar por Grupo</label>
          <select
            v-model="filtroGrupo"
            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
            @change="aplicarFiltros"
          >
            <option value="">Todos los grupos</option>
            <option v-for="g in grupos" :key="g.id" :value="g.id">{{ g.codigo_grupo }}</option>
          </select>
        </div>

        <button
          v-if="filtroDocente || filtroSala || filtroGrupo"
          @click="limpiarFiltros"
          class="text-sm text-red-600 hover:text-red-800 font-medium self-end py-1.5"
        >
          Limpiar Filtros
        </button>
      </div>

      <!-- Navigation Tabs -->
      <div class="border-b border-gray-200">
        <nav class="flex space-x-6" aria-label="Tabs">
          <button
            v-if="rolUsuario !== 'Alumno'"
            @click="tabActiva = 'docentes'"
            class="border-b-2 py-4 px-1 text-sm font-medium transition"
            :class="tabActiva === 'docentes' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
          >
            {{ rolUsuario === 'Docente' ? 'Mi Disponibilidad' : 'Disponibilidad de Docentes' }}
          </button>
          <button
            @click="tabActiva = 'grupos'"
            class="border-b-2 py-4 px-1 text-sm font-medium transition"
            :class="tabActiva === 'grupos' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
          >
            {{ rolUsuario === 'Alumno' ? 'Mis Horarios de Clases' : (rolUsuario === 'Docente' ? 'Mis Clases/Grupos' : 'Horarios de Grupos / Aulas') }}
          </button>
        </nav>
      </div>

      <!-- Tab Content: Docentes -->
      <div v-if="tabActiva === 'docentes' && rolUsuario !== 'Alumno'" class="space-y-4">
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th v-if="rolUsuario !== 'Docente'" class="px-4 py-3 text-left font-semibold text-gray-600">Docente</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Día de la Semana</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Hora Inicio</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Hora Fin</th>
                <th v-if="puedeGestionar" class="px-4 py-3 text-left font-semibold text-gray-600">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="hd in horariosDocentes" :key="hd.id" class="hover:bg-gray-50">
                <td v-if="rolUsuario !== 'Docente'" class="px-4 py-3 font-medium text-gray-900">
                  {{ hd.docente.usuario.nombres }} {{ hd.docente.usuario.apellidos }}
                </td>
                <td class="px-4 py-3 text-gray-600">{{ hd.dia_semana }}</td>
                <td class="px-4 py-3 text-gray-600">{{ formatTime(hd.hora_inicio) }}</td>
                <td class="px-4 py-3 text-gray-600">{{ formatTime(hd.hora_fin) }}</td>
                <td v-if="puedeGestionar" class="px-4 py-3">
                  <div class="flex items-center">
                    <button
                      v-if="puedeGestionar"
                      @click="abrirEditarDocente(hd)"
                      class="rounded px-2 py-1 text-xs bg-yellow-50 text-yellow-700 hover:bg-yellow-100 border border-yellow-200 cursor-pointer"
                      title="Editar"
                    >
                      <component :is="Lucide.Pencil" class="w-4 h-4 shrink-0" />
                    </button>
                    <button
                      v-if="puedeEliminar"
                      @click="confirmarEliminarDocente(hd)"
                      class="rounded px-2 py-1 text-xs bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 cursor-pointer"
                      title="Eliminar"
                    >
                      <component :is="Lucide.Trash2" class="w-4 h-4 shrink-0" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="horariosDocentes.length === 0">
                <td :colspan="rolUsuario === 'Docente' ? 3 : 5" class="px-4 py-8 text-center text-gray-400">
                  No hay disponibilidades registradas.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab Content: Grupos -->
      <div v-if="tabActiva === 'grupos'" class="space-y-4">
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Grupo</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Curso</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Docente</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Sala (Aula)</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Día de la Semana</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Hora Inicio</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Hora Fin</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo de Sesión</th>
                <th v-if="puedeGestionar" class="px-4 py-3 text-left font-semibold text-gray-600">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="hg in horariosGrupos" :key="hg.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 font-semibold text-gray-900">{{ hg.grupo.codigo_grupo }}</td>
                <td class="px-4 py-3 text-gray-600">{{ hg.grupo.curso.nombre }}</td>
                <td class="px-4 py-3 text-gray-600">
                  {{ hg.grupo.docente.usuario.nombres }} {{ hg.grupo.docente.usuario.apellidos }}
                </td>
                <td class="px-4 py-3 text-indigo-700 font-medium">{{ hg.sala.nombre }}</td>
                <td class="px-4 py-3 text-gray-600">{{ hg.dia_semana }}</td>
                <td class="px-4 py-3 text-gray-600">{{ formatTime(hg.hora_inicio) }}</td>
                <td class="px-4 py-3 text-gray-600">{{ formatTime(hg.hora_fin) }}</td>
                <td class="px-4 py-3 text-gray-600">
                  <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700 font-medium">
                    {{ hg.tipo_sesion ?? 'Regular' }}
                  </span>
                </td>
                <td v-if="puedeGestionar" class="px-4 py-3">
                  <div class="flex items-center">
                    <button
                      v-if="puedeGestionar"
                      @click="abrirEditarGrupo(hg)"
                      class="rounded px-2 py-1 text-xs bg-yellow-50 text-yellow-700 hover:bg-yellow-100 border border-yellow-200 cursor-pointer"
                      title="Editar"
                    >
                      <component :is="Lucide.Pencil" class="w-4 h-4 shrink-0" />
                    </button>
                    <button
                      v-if="puedeEliminar"
                      @click="confirmarEliminarGrupo(hg)"
                      class="rounded px-2 py-1 text-xs bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 cursor-pointer"
                      title="Eliminar"
                    >
                      <component :is="Lucide.Trash2" class="w-4 h-4 shrink-0" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="horariosGrupos.length === 0">
                <td :colspan="puedeGestionar ? 9 : 8" class="px-4 py-8 text-center text-gray-400">
                  No hay horarios de grupos registrados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import * as Lucide from '@lucide/vue';
import CreateDocente from './Docente/Create.vue';
import CreateGrupo from './Grupo/Create.vue';

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  if (!path) return '#';
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const props = defineProps({
  horariosDocentes: Array,
  horariosGrupos: Array,
  docentes: Array,
  grupos: Array,
  salas: Array,
  filters: Object,
});

const page = usePage();
const rolUsuario = computed(() => page.props.auth.rol);
const puedeGestionar = computed(() => ['Propietario', 'Secretaria'].includes(rolUsuario.value));
const puedeEliminar = computed(() => rolUsuario.value === 'Propietario');

const tabActiva = ref(rolUsuario.value === 'Alumno' ? 'grupos' : 'docentes');

const filtroDocente = ref(props.filters.docente_id || '');
const filtroSala = ref(props.filters.sala_id || '');
const filtroGrupo = ref(props.filters.grupo_id || '');

// Modal state
const mostrarFormDocente = ref(false);
const editandoDocente = ref(null);
const mostrarFormGrupo = ref(false);
const editandoGrupo = ref(null);

function formatTime(timeStr) {
  if (!timeStr) return '';
  return timeStr.substring(0, 5);
}

function aplicarFiltros() {
  router.get(buildUrl('/horarios'), {
    docente_id: filtroDocente.value,
    sala_id: filtroSala.value,
    grupo_id: filtroGrupo.value,
  }, {
    preserveState: true,
    replace: true,
  });
}

function limpiarFiltros() {
  filtroDocente.value = '';
  filtroSala.value = '';
  filtroGrupo.value = '';
  aplicarFiltros();
}

// Docente availability actions
function abrirNuevoDocente() {
  editandoDocente.value = null;
  mostrarFormDocente.value = true;
}

function abrirEditarDocente(hd) {
  editandoDocente.value = hd;
  mostrarFormDocente.value = true;
}

function cerrarFormDocente() {
  mostrarFormDocente.value = false;
  editandoDocente.value = null;
}

function confirmarEliminarDocente(hd) {
  if (confirm('¿Está seguro de eliminar esta disponibilidad?')) {
    router.delete(buildUrl(`/horarios/docentes/${hd.id}`));
  }
}

// Group schedule actions
function abrirNuevoGrupo() {
  editandoGrupo.value = null;
  mostrarFormGrupo.value = true;
}

function abrirEditarGrupo(hg) {
  editandoGrupo.value = hg;
  mostrarFormGrupo.value = true;
}

function cerrarFormGrupo() {
  mostrarFormGrupo.value = false;
  editandoGrupo.value = null;
}

function confirmarEliminarGrupo(hg) {
  if (confirm('¿Está seguro de eliminar este horario del grupo?')) {
    router.delete(buildUrl(`/horarios/grupos/${hg.id}`));
  }
}
</script>
