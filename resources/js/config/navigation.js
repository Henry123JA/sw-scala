/**
 * Configuración centralizada de navegación para Scala Musical.
 * Desacoplada de PostgreSQL y alineada con la seguridad RBAC de Laravel.
 */
export const navigation = [
  {
    label: 'Dashboard',
    route: 'dashboard',
    path: '/dashboard',
    icon: 'LayoutDashboard',
    roles: ['Propietario']
  },
  {
    label: 'Usuarios',
    icon: 'Users',
    roles: ['Propietario', 'Secretaria'],
    children: [
      {
        label: 'Gestión General',
        route: 'usuarios.index',
        path: '/usuarios',
        icon: 'UserCog',
        roles: ['Propietario']
      },
      {
        label: 'Alumnos',
        route: 'alumnos.index',
        path: '/alumnos',
        icon: 'GraduationCap',
        roles: ['Propietario', 'Secretaria']
      },
      {
        label: 'Docentes',
        route: 'docentes.index',
        path: '/docentes',
        icon: 'UserCheck',
        roles: ['Propietario', 'Secretaria']
      },
      {
        label: 'Secretarias',
        route: 'secretarias.index',
        path: '/secretarias',
        icon: 'Shield',
        roles: ['Propietario']
      }
    ]
  },
  {
    label: 'Aulas',
    route: 'aulas.index',
    path: '/aulas',
    icon: 'DoorOpen',
    roles: ['Propietario', 'Secretaria']
  },
  {
    label: 'Cursos',
    route: 'cursos.index',
    path: '/cursos',
    icon: 'BookOpen',
    roles: ['Propietario', 'Secretaria']
  },
  {
    label: 'Grupos',
    route: 'grupos.index',
    path: '/grupos',
    icon: 'Folder',
    roles: ['Propietario', 'Secretaria', 'Docente']
  },
  {
    label: 'Horarios',
    route: 'horarios.index',
    path: '/horarios',
    icon: 'Clock',
    roles: ['Propietario', 'Secretaria', 'Docente', 'Alumno']
  },
  {
    label: 'Inscripciones',
    route: 'inscripciones.index',
    path: '/inscripciones',
    icon: 'UserPlus',
    roles: ['Propietario', 'Secretaria', 'Docente', 'Alumno']
  },
  {
    label: 'Reportes',
    route: 'reportes.index',
    path: '/reportes',
    icon: 'FileText',
    roles: ['Propietario']
  },
  {
    label: 'Bitácora',
    route: 'bitacora.index',
    path: '/bitacora',
    icon: 'History',
    roles: ['Propietario']
  }
];

/**
 * Obtiene los elementos de navegación autorizados para un rol específico.
 * @param {string} role - Nombre del rol del usuario autenticado (ej: 'Propietario', 'Secretaria', 'Docente', 'Alumno')
 * @returns {Array} Lista filtrada de opciones de navegación con sus subopciones autorizadas.
 */
export function getNavForRole(role) {
  if (!role) return [];
  return navigation
    .filter(item => item.roles.includes(role))
    .map(item => {
      if (item.children) {
        const allowedChildren = item.children.filter(child => child.roles.includes(role));
        return {
          ...item,
          children: allowedChildren
        };
      }
      return item;
    })
    .filter(item => !item.children || item.children.length > 0);
}

