<template>
  <Head title="Academia de Música y Arte SCALA - Portal Corporativo" />

  <div class="min-h-screen bg-[#F8FFFD] text-[#1F2937] font-sans flex flex-col selection:bg-[#14B8A6] selection:text-white">
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- 1. TOP BAR INSTITUCIONAL (Inspirado en barra superior U-Digital)        -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-[#0B3B36] text-white text-xs border-b border-teal-900/60">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-2 flex flex-wrap items-center justify-between gap-2">
        <!-- Info rápida y Redes Sociales Oficiales -->
        <div class="flex items-center gap-4 flex-wrap">
          <span class="hidden sm:inline-flex items-center gap-1.5 text-teal-100">
            <component :is="Lucide.MapPin" class="w-3.5 h-3.5 text-[#2DD4BF]" />
            Santa Cruz de la Sierra, Bolivia
          </span>
          <span class="inline-flex items-center gap-1.5 text-teal-100">
            <component :is="Lucide.Phone" class="w-3.5 h-3.5 text-[#2DD4BF]" />
            +591 700-45890
          </span>
          <div class="flex items-center gap-2.5 pl-1 border-l border-teal-700/70">
            <a
              :href="redes?.facebook || 'https://www.facebook.com/p/Academia-De-Musica-y-Arte-Scala-100064673172866/'"
              target="_blank"
              rel="noopener noreferrer"
              class="hover:text-[#5EEAD4] transition-colors inline-flex items-center gap-1 font-medium"
              title="Facebook Oficial Academia SCALA"
            >
              <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
              <span class="hidden md:inline">Facebook</span>
            </a>
            <a
              :href="redes?.instagram || 'https://www.instagram.com/academia.scala/'"
              target="_blank"
              rel="noopener noreferrer"
              class="hover:text-[#5EEAD4] transition-colors inline-flex items-center gap-1 font-medium"
              title="Instagram Oficial Academia SCALA"
            >
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
              <span class="hidden md:inline">Instagram</span>
            </a>
          </div>
        </div>

        <!-- Accesos Rápidos Derecha (Estilo U-Digital | FAQ | Perfil | Webmail) -->
        <div class="flex items-center gap-2.5 sm:gap-3 ml-auto">
          <Link
            :href="authUser ? buildUrl('/dashboard') : buildUrl('/login')"
            class="inline-flex items-center gap-1.5 bg-amber-400 hover:bg-amber-300 text-gray-950 font-extrabold px-2.5 py-1 rounded-md shadow-2xs transition-colors"
          >
            <component :is="Lucide.Wifi" class="w-3.5 h-3.5" />
            <span>SCALA-Digital</span>
          </Link>
          <span class="text-teal-600">|</span>
          <a href="#institucion" class="hover:text-amber-300 transition-colors font-medium">
            Institución
          </a>
          <span class="text-teal-600">|</span>
          <Link :href="buildUrl('/politica-privacidad')" class="hover:text-amber-300 transition-colors font-medium">
            Privacidad
          </Link>
          <span class="text-teal-600 hidden sm:inline">|</span>
          <Link :href="buildUrl('/terminos-condiciones')" class="hover:text-amber-300 transition-colors font-medium hidden sm:inline">
            Términos
          </Link>
          <span class="text-teal-600">|</span>
          <Link
            :href="authUser ? buildUrl('/dashboard') : buildUrl('/login')"
            class="hover:text-amber-300 transition-colors font-bold inline-flex items-center gap-1"
          >
            <component :is="Lucide.LogIn" class="w-3.5 h-3.5" />
            <span>{{ authUser ? 'Mi Panel' : 'Aula Virtual' }}</span>
          </Link>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- 2. CABECERA PRINCIPAL Y NAVEGACIÓN CORPORATIVA (Figura 7.1 y 7.2)       -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <header class="bg-gradient-to-b from-[#0F766E] to-[#0D5F58] text-white shadow-md sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-4 pb-3">
        <!-- Logo y Título Institucional Centrado (Estilo UAGRM) -->
        <div class="flex items-center justify-between lg:justify-center relative">
          <a href="#inicio" class="flex items-center gap-3.5 group">
            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-white p-0.5 border-2 border-amber-300/90 flex items-center justify-center shadow-md group-hover:scale-105 transition-transform overflow-hidden">
              <img
                :src="buildUrl('/images/academia-scala.jpg')"
                alt="Logo Academia de Música SCALA"
                class="w-full h-full object-cover rounded-full"
              />
            </div>
            <div class="text-left">
              <p class="text-[11px] sm:text-sm uppercase tracking-[0.28em] text-teal-100 font-semibold">
                Academia de Música y Arte
              </p>
              <h1 class="text-2xl sm:text-4xl font-serif font-black tracking-wide text-white leading-none mt-0.5">
                SCALA
              </h1>
            </div>
          </a>

          <!-- Botón Menú Hamburguesa (Móvil - Figura 7.16) -->
          <button
            type="button"
            @click="menuMovilAbierto = !menuMovilAbierto"
            class="lg:hidden p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white focus:outline-none cursor-pointer"
            aria-label="Abrir menú de navegación"
          >
            <component :is="menuMovilAbierto ? Lucide.X : Lucide.Menu" class="w-6 h-6" />
          </button>
        </div>

        <!-- Barra de Navegación Horizontal + Buscador Integrado -->
        <div class="hidden lg:flex items-center justify-between gap-4 mt-4 pt-2.5 border-t border-white/15 text-xs font-semibold">
          <nav class="flex items-center gap-5">
            <a href="#inicio" class="inline-flex items-center gap-1.5 text-amber-300 hover:text-white transition-colors">
              <component :is="Lucide.Home" class="w-4 h-4" />
              <span>Inicio</span>
            </a>
            <a href="#institucion" class="inline-flex items-center gap-1 text-white/90 hover:text-amber-300 transition-colors">
              <span>Institución</span>
              <component :is="Lucide.ChevronDown" class="w-3.5 h-3.5 opacity-80" />
            </a>
            <a href="#servicios" class="inline-flex items-center gap-1 text-white/90 hover:text-amber-300 transition-colors">
              <span>Cursos y Servicios</span>
              <component :is="Lucide.ChevronDown" class="w-3.5 h-3.5 opacity-80" />
            </a>
            <a href="#actividades" class="text-white/90 hover:text-amber-300 transition-colors">
              Actividades y Recitales
            </a>
            <a href="#ubicacion" class="text-white/90 hover:text-amber-300 transition-colors">
              Ubicación
            </a>
            <a href="#contacto" class="text-white/90 hover:text-amber-300 transition-colors">
              Contacto
            </a>
            <Link :href="buildUrl('/login')" class="text-white/90 hover:text-amber-300 transition-colors">
              Aulas Virtuales
            </Link>
            <Link :href="buildUrl('/politica-privacidad')" class="text-white/90 hover:text-amber-300 transition-colors">
              Políticas y Términos
            </Link>
          </nav>

          <!-- Buscador Rápido de Cursos + Botón Iniciar Sesión -->
          <div class="flex items-center gap-2.5">
            <div class="relative flex items-center">
              <input
                v-model="busquedaCurso"
                type="text"
                placeholder="Buscar curso o instrumento..."
                @keyup.enter="irAServicios"
                class="w-48 xl:w-56 bg-white text-gray-800 placeholder-gray-400 text-xs px-3 py-1.5 rounded-l-md focus:outline-none"
              />
              <button
                type="button"
                @click="irAServicios"
                class="bg-emerald-500 hover:bg-emerald-400 text-white px-2.5 py-1.5 rounded-r-md transition-colors cursor-pointer"
                title="Buscar curso"
              >
                <component :is="Lucide.Search" class="w-4 h-4" />
              </button>
            </div>

            <Link
              :href="authUser ? buildUrl('/dashboard') : buildUrl('/login')"
              class="inline-flex items-center gap-1.5 bg-amber-400 hover:bg-amber-300 text-gray-950 font-extrabold text-xs px-3.5 py-1.5 rounded-md shadow-xs transition-colors shrink-0"
            >
              <component :is="Lucide.LogIn" class="w-3.5 h-3.5" />
              <span>{{ authUser ? 'Mi Panel' : 'Iniciar Sesión' }}</span>
            </Link>
          </div>
        </div>

        <!-- Menú Desplegable Móvil (Figura 7.16 - Visualización adaptable) -->
        <div
          v-if="menuMovilAbierto"
          class="lg:hidden mt-3 pt-3 border-t border-white/20 space-y-2 pb-2"
        >
          <div class="grid grid-cols-2 gap-2 text-xs font-semibold">
            <a href="#inicio" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 flex items-center gap-2">
              <component :is="Lucide.Home" class="w-4 h-4 text-amber-300" />
              Inicio
            </a>
            <a href="#institucion" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 flex items-center gap-2">
              <component :is="Lucide.Landmark" class="w-4 h-4 text-amber-300" />
              Misión y Visión
            </a>
            <a href="#servicios" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 flex items-center gap-2">
              <component :is="Lucide.Music2" class="w-4 h-4 text-amber-300" />
              Cursos y Servicios
            </a>
            <a href="#ubicacion" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 flex items-center gap-2">
              <component :is="Lucide.MapPin" class="w-4 h-4 text-amber-300" />
              Ubicación
            </a>
            <a href="#contacto" @click="menuMovilAbierto = false" class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 flex items-center gap-2">
              <component :is="Lucide.Mail" class="w-4 h-4 text-amber-300" />
              Contacto
            </a>
            <Link :href="buildUrl('/login')" class="px-3 py-2 rounded-lg bg-amber-400 text-gray-950 font-extrabold flex items-center gap-2">
              <component :is="Lucide.LogIn" class="w-4 h-4" />
              Iniciar Sesión
            </Link>
          </div>

          <div class="pt-2 flex items-center">
            <input
              v-model="busquedaCurso"
              type="text"
              placeholder="Buscar curso o instrumento..."
              class="w-full bg-white text-gray-800 text-xs px-3 py-2 rounded-l-lg focus:outline-none"
            />
            <button
              type="button"
              @click="irAServicios"
              class="bg-emerald-500 text-white px-3 py-2 rounded-r-lg"
            >
              <component :is="Lucide.Search" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- 3. HERO BANNER CON ONDA ARTÍSTICA (Réplica de composición visual)       -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <section id="inicio" class="relative h-[430px] sm:h-[500px] overflow-hidden bg-[#082623] select-none">
      <!-- Imagen de fondo musical con transición de slides -->
      <div
        class="absolute inset-0 bg-cover bg-center transition-all duration-700 scale-105"
        :style="{ backgroundImage: `url('${slideActual.imagen}')` }"
      ></div>
      <div class="absolute inset-0 bg-gradient-to-r from-[#06221F]/85 via-[#09302B]/70 to-[#06221F]/75"></div>

      <!-- Flechas de navegación del carrusel -->
      <button
        type="button"
        @click="prevSlide"
        class="absolute left-3 sm:left-6 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/25 hover:bg-black/50 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer"
        aria-label="Anterior"
      >
        <component :is="Lucide.ChevronLeft" class="w-6 h-6" />
      </button>
      <button
        type="button"
        @click="nextSlide"
        class="absolute right-3 sm:right-6 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/25 hover:bg-black/50 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer"
        aria-label="Siguiente"
      >
        <component :is="Lucide.ChevronRight" class="w-6 h-6" />
      </button>

      <!-- Sello Institucional Central + Arte Oficial de la Academia (Hero) -->
      <div class="relative z-10 max-w-7xl mx-auto h-full px-4 sm:px-6 flex flex-col justify-center items-center text-center pb-20 sm:pb-24">
        <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6 bg-black/35 backdrop-blur-xs px-6 py-4 rounded-2xl border border-white/20 shadow-2xl">
          <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl border-2 border-amber-300 bg-white p-1 flex items-center justify-center shrink-0 shadow-lg overflow-hidden">
            <img
              :src="buildUrl('/images/academia-scala.jpg')"
              alt="Academia de Música SCALA"
              class="w-full h-full object-cover rounded-xl"
            />
          </div>
          <div class="hidden sm:block w-0.5 h-20 bg-white/60"></div>
          <div class="text-center sm:text-left text-white">
            <p class="text-xs sm:text-sm font-medium tracking-widest uppercase text-teal-200">
              {{ slideActual.subtitulo }}
            </p>
            <h2 class="text-xl sm:text-3xl font-black tracking-wide uppercase mt-0.5">
              {{ slideActual.titulo }}
            </h2>
            <p class="text-xs sm:text-sm text-gray-200 mt-0.5">
              Santa Cruz - Bolivia
            </p>
          </div>
        </div>

        <!-- Botones de Acción Rápida en el Hero -->
        <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
          <a
            href="#servicios"
            class="px-5 py-2.5 rounded-xl bg-[#14B8A6] hover:bg-[#0D9488] text-white font-extrabold text-xs sm:text-sm shadow-lg transition-all inline-flex items-center gap-2"
          >
            <component :is="Lucide.Sparkles" class="w-4 h-4" />
            Explorar Cursos y Servicios
          </a>
          <button
            type="button"
            @click="abrirAgenteChat"
            class="px-5 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-gray-950 font-extrabold text-xs sm:text-sm shadow-lg transition-all inline-flex items-center gap-2 cursor-pointer"
          >
            <component :is="Lucide.Bot" class="w-4 h-4" />
            Consultar al Agente IA
          </button>
          <Link
            :href="buildUrl('/login')"
            class="px-5 py-2.5 rounded-xl bg-white/95 hover:bg-white text-[#0F766E] font-extrabold text-xs sm:text-sm shadow-lg transition-all inline-flex items-center gap-2"
          >
            <component :is="Lucide.LogIn" class="w-4 h-4" />
            Ingresar al Sistema Académico
          </Link>
        </div>
      </div>

      <!-- Onda Curva Tricolor Inferior + Escudo y Lema (Inspirada en la curva inferior de la imagen) -->
      <div class="absolute inset-x-0 bottom-0 z-10 pointer-events-none">
        <svg viewBox="0 0 1440 210" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-36 sm:h-48 preserve-3d" preserveAspectRatio="none">
          <!-- Franja Azul/Turquesa Profundo -->
          <path d="M290 210 C 450 85, 620 80, 820 145 C 1020 210, 1220 90, 1440 10 L 1440 210 Z" fill="#0F766E" />
          <!-- Franja Coral/Dorado Artístico -->
          <path d="M350 210 C 510 95, 680 95, 870 160 C 1050 215, 1250 110, 1440 30 L 1440 210 Z" fill="#14B8A6" />
          <!-- Franja Esmeralda Principal Derecha -->
          <path d="M680 210 C 820 150, 1040 85, 1440 45 L 1440 210 Z" fill="#047857" />
        </svg>

        <!-- Emblema circular sobre la onda con la foto oficial de SCALA -->
        <div class="absolute bottom-4 sm:bottom-6 left-1/2 -translate-x-12 sm:-translate-x-24 w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white p-1 shadow-xl border-2 border-amber-300 flex items-center justify-center overflow-hidden">
          <img
            :src="buildUrl('/images/academia-scala.jpg')"
            alt="Emblema SCALA"
            class="w-full h-full object-cover rounded-full"
          />
        </div>

        <!-- Lema en cursiva en la esquina inferior derecha -->
        <div class="absolute bottom-3 sm:bottom-5 right-4 sm:right-14 text-right max-w-xs sm:max-w-md">
          <p class="text-white font-black italic text-sm sm:text-xl leading-snug drop-shadow-md">
            Formando talento con innovación,<br />
            <span class="text-amber-300">liderando con valores artísticos!!</span>
          </p>
        </div>
      </div>
    </section>

    <!-- Barra de Indicadores Institucionales -->
    <section class="bg-white border-b border-[#DDEFEA] py-4 shadow-2xs">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
        <div class="flex items-center justify-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
            <component :is="Lucide.BookOpen" class="w-5 h-5" />
          </div>
          <div class="text-left">
            <span class="text-lg font-black text-gray-900 block leading-none">{{ metricas?.cursos_activos || 6 }}+</span>
            <span class="text-[11px] text-gray-500 font-semibold">Programas Musicales</span>
          </div>
        </div>
        <div class="flex items-center justify-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
            <component :is="Lucide.UserCheck" class="w-5 h-5" />
          </div>
          <div class="text-left">
            <span class="text-lg font-black text-gray-900 block leading-none">{{ metricas?.docentes_activos || 5 }}+</span>
            <span class="text-[11px] text-gray-500 font-semibold">Docentes Especialistas</span>
          </div>
        </div>
        <div class="flex items-center justify-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
            <component :is="Lucide.BrainCircuit" class="w-5 h-5" />
          </div>
          <div class="text-left">
            <span class="text-lg font-black text-gray-900 block leading-none">IA + XAI</span>
            <span class="text-[11px] text-gray-500 font-semibold">Seguimiento Predictivo</span>
          </div>
        </div>
        <div class="flex items-center justify-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
            <component :is="Lucide.ShieldCheck" class="w-5 h-5" />
          </div>
          <div class="text-left">
            <span class="text-lg font-black text-gray-900 block leading-none">100%</span>
            <span class="text-[11px] text-gray-500 font-semibold">Expediente Digital 360°</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- 4. INFORMACIÓN INSTITUCIONAL: MISIÓN, VISIÓN Y VALORES (Figura 7.3)     -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <section id="institucion" class="py-14 bg-[#F8FFFD]">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 space-y-8">
        <div class="text-center max-w-3xl mx-auto">
          <span class="badge-menta uppercase tracking-wider">Información Institucional</span>
          <h2 class="text-2xl sm:text-3xl font-black text-gray-900 mt-2">
            Nuestra Identidad, Misión y Visión
          </h2>
          <p class="text-xs sm:text-sm text-gray-600 mt-2 leading-relaxed">
            La <strong>Academia de Música y Arte SCALA</strong> es una institución dedicada a la enseñanza musical personalizada y grupal en Santa Cruz de la Sierra, integrando excelencia pedagógica con tecnología de seguimiento académico predictivo.
          </p>
        </div>

        <!-- Presentación Visual con la Imagen Oficial de la Academia -->
        <div class="card-menta p-6 sm:p-8 bg-white border border-[#DDEFEA] shadow-sm">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
            <div class="lg:col-span-5 flex justify-center">
              <div class="relative max-w-sm w-full rounded-2xl overflow-hidden border-2 border-[#14B8A6]/40 shadow-lg bg-white p-2">
                <img
                  :src="buildUrl('/images/academia-scala.jpg')"
                  alt="Arte Oficial Academia de Música SCALA"
                  class="w-full h-auto max-h-80 object-contain rounded-xl mx-auto"
                />
              </div>
            </div>
            <div class="lg:col-span-7 space-y-4">
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#D1FAE5] text-[#0F766E] text-xs font-extrabold">
                <component :is="Lucide.Award" class="w-4 h-4" />
                <span>Formación Musical para Todas las Edades y Niveles</span>
              </div>
              <h3 class="text-xl sm:text-2xl font-black text-gray-900">
                Academia de Música y Arte SCALA
              </h3>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                Formamos músicos integrales en <strong>Piano, Guitarra, Violín, Saxofón, Batería, Bajo y Técnica Vocal</strong>, articulando clases prácticas individuales y ensambles grupales con un sistema de gestión académica que acompaña de cerca la evolución técnica y artística de cada estudiante.
              </p>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-1">
                <div class="p-3 rounded-xl bg-[#F8FFFD] border border-[#DDEFEA]">
                  <span class="text-xs font-black text-[#0F766E] block">Clases Prácticas</span>
                  <span class="text-[11px] text-gray-500">Individuales y Grupales</span>
                </div>
                <div class="p-3 rounded-xl bg-[#F8FFFD] border border-[#DDEFEA]">
                  <span class="text-xs font-black text-[#0F766E] block">Todos los Niveles</span>
                  <span class="text-[11px] text-gray-500">Inicial, Intermedio y Avanzado</span>
                </div>
                <div class="p-3 rounded-xl bg-[#F8FFFD] border border-[#DDEFEA] col-span-2 sm:col-span-1">
                  <span class="text-xs font-black text-[#0F766E] block">Seguimiento 360°</span>
                  <span class="text-[11px] text-gray-500">Plataforma SCALA-Digital</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Misión -->
          <div class="card-menta p-6 border-t-4 border-t-[#14B8A6] space-y-3 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 rounded-2xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center">
              <component :is="Lucide.Target" class="w-6 h-6" />
            </div>
            <h3 class="text-lg font-black text-gray-900">Misión Institucional</h3>
            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
              Brindar una formación musical y artística integral de alta calidad a niños, jóvenes y adultos, combinando la práctica instrumental, la lectura teórica y la expresión escénica con un acompañamiento pedagógico continuo y personalizado que potencie el talento de cada estudiante.
            </p>
          </div>

          <!-- Visión -->
          <div class="card-menta p-6 border-t-4 border-t-[#0F766E] space-y-3 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 rounded-2xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center">
              <component :is="Lucide.Eye" class="w-6 h-6" />
            </div>
            <h3 class="text-lg font-black text-gray-900">Visión Institucional</h3>
            <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
              Consolidarnos como la academia referente en educación musical e innovación educativa en Bolivia, reconocida por su excelencia interpretativa, su metodología formativa moderna y el uso de analítica predictiva para garantizar la permanencia y el éxito artístico de sus alumnos.
            </p>
          </div>

          <!-- Valores Institucionales -->
          <div class="card-menta p-6 border-t-4 border-t-amber-400 space-y-3 hover:shadow-md transition-shadow">
            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center">
              <component :is="Lucide.HeartHandshake" class="w-6 h-6" />
            </div>
            <h3 class="text-lg font-black text-gray-900">Valores y Compromiso</h3>
            <ul class="text-xs sm:text-sm text-gray-600 space-y-2">
              <li class="flex items-start gap-2">
                <component :is="Lucide.CheckCircle2" class="w-4 h-4 text-[#14B8A6] shrink-0 mt-0.5" />
                <span><strong>Disciplina y Constancia:</strong> Hábito formativo en la práctica musical.</span>
              </li>
              <li class="flex items-start gap-2">
                <component :is="Lucide.CheckCircle2" class="w-4 h-4 text-[#14B8A6] shrink-0 mt-0.5" />
                <span><strong>Sensibilidad Artística:</strong> Desarrollo expresivo y escénico en recitales.</span>
              </li>
              <li class="flex items-start gap-2">
                <component :is="Lucide.CheckCircle2" class="w-4 h-4 text-[#14B8A6] shrink-0 mt-0.5" />
                <span><strong>Innovación Pedagógica:</strong> Seguimiento 360° y alertas tempranas.</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- 5. SERVICIOS, CURSOS Y ACTIVIDADES OFRECIDAS (Figura 7.4)               -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <section id="servicios" class="py-14 bg-white border-y border-[#DDEFEA]">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 space-y-10">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
          <div>
            <span class="badge-menta uppercase tracking-wider">Oferta Académica y Artística</span>
            <h2 class="text-2xl sm:text-3xl font-black text-gray-900 mt-2">
              Servicios, Cursos y Actividades Ofrecidas
            </h2>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">
              Programas estructurados por niveles en modalidad individual y grupal, con salas acustizadas y seguimiento personalizado.
            </p>
          </div>

          <!-- Filtro rápido de modalidad -->
          <div class="flex items-center gap-2 flex-wrap">
            <button
              type="button"
              @click="filtroModalidad = 'TODOS'; busquedaCurso = ''"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer"
              :class="filtroModalidad === 'TODOS' ? 'bg-[#0F766E] text-white' : 'bg-[#F0FDF9] text-[#0F766E] border border-[#DDEFEA]'"
            >
              Todos ({{ listaCursos.length }})
            </button>
            <button
              type="button"
              @click="filtroModalidad = 'individual'"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer"
              :class="filtroModalidad === 'individual' ? 'bg-[#0F766E] text-white' : 'bg-[#F0FDF9] text-[#0F766E] border border-[#DDEFEA]'"
            >
              Enseñanza Individual
            </button>
            <button
              type="button"
              @click="filtroModalidad = 'grupal'"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer"
              :class="filtroModalidad === 'grupal' ? 'bg-[#0F766E] text-white' : 'bg-[#F0FDF9] text-[#0F766E] border border-[#DDEFEA]'"
            >
              Ensamble Grupal
            </button>
          </div>
        </div>

        <!-- Grilla de Cursos Musicales -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="curso in cursosFiltrados"
            :key="curso.id || curso.nombre"
            class="card-menta p-5 flex flex-col justify-between hover:border-[#14B8A6] transition-all group"
          >
            <div class="space-y-3">
              <div class="flex items-center justify-between gap-2">
                <div class="w-11 h-11 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center group-hover:bg-[#14B8A6] group-hover:text-white transition-colors">
                  <component :is="getIconoCurso(curso.nombre)" class="w-5 h-5" />
                </div>
                <span
                  class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase border"
                  :class="curso.tipo_ensenanza === 'individual' ? 'bg-teal-50 text-teal-800 border-teal-200' : 'bg-amber-50 text-amber-800 border-amber-200'"
                >
                  Modalidad {{ curso.tipo_ensenanza || 'Individual' }}
                </span>
              </div>

              <h3 class="text-base font-black text-gray-900">
                {{ curso.nombre }}
              </h3>
              <p class="text-xs text-gray-600 leading-relaxed">
                {{ curso.descripcion }}
              </p>
            </div>

            <div class="pt-4 mt-4 border-t border-[#DDEFEA] flex items-center justify-between text-xs">
              <span class="text-gray-500 font-semibold flex items-center gap-1">
                <component :is="Lucide.Calendar" class="w-3.5 h-3.5 text-[#0F766E]" />
                Ciclo: {{ curso.duracion_estandar || 12 }} semanas
              </span>
              <a
                href="#contacto"
                @click="seleccionarCursoContacto(curso.nombre)"
                class="font-extrabold text-[#0F766E] hover:text-[#14B8A6] inline-flex items-center gap-1"
              >
                <span>Solicitar cupo</span>
                <component :is="Lucide.ArrowRight" class="w-3.5 h-3.5" />
              </a>
            </div>
          </div>
        </div>

        <!-- Actividades Artísticas y Servicios Complementarios -->
        <div id="actividades" class="pt-4 space-y-4">
          <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
            <component :is="Lucide.Sparkles" class="w-5 h-5 text-[#14B8A6]" />
            Actividades Formativas y Servicios Especializados
          </h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
              v-for="act in actividades"
              :key="act.titulo"
              class="p-4 rounded-2xl bg-[#F0FDF9] border border-[#DDEFEA] space-y-2"
            >
              <div class="w-9 h-9 rounded-xl bg-white text-[#0F766E] border border-[#DDEFEA] flex items-center justify-center">
                <component :is="act.icono" class="w-4 h-4" />
              </div>
              <h4 class="text-sm font-extrabold text-gray-900">{{ act.titulo }}</h4>
              <p class="text-xs text-gray-600 leading-relaxed">{{ act.descripcion }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- 6. UBICACIÓN DE LA ACADEMIA Y FORMULARIO DE CONTACTO (Fig. 7.5 y 7.6)   -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <section class="py-14 bg-[#F8FFFD]">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- COLUMNA IZQUIERDA: UBICACIÓN DE LA ACADEMIA (Figura 7.5) -->
        <div id="ubicacion" class="lg:col-span-6 space-y-5">
          <div>
            <span class="badge-menta uppercase tracking-wider">Dónde Encontrarnos</span>
            <h2 class="text-2xl font-black text-gray-900 mt-2">
              Ubicación de la Academia SCALA
            </h2>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">
              Visítanos en nuestras instalaciones con salas acustizadas para piano, cuerdas, percusión, vientos y canto.
            </p>
          </div>

          <div class="card-menta p-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
                  <component :is="Lucide.MapPin" class="w-4 h-4" />
                </div>
                <div>
                  <span class="font-extrabold text-gray-900 block">Dirección Institucional</span>
                  <span class="text-gray-600 block mt-0.5">Santa Cruz de la Sierra, Bolivia (Sede Central Academia SCALA)</span>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
                  <component :is="Lucide.Clock" class="w-4 h-4" />
                </div>
                <div>
                  <span class="font-extrabold text-gray-900 block">Horarios de Atención</span>
                  <span class="text-gray-600 block mt-0.5">Lun a Vie: 08:00 - 20:00<br />Sábados: 09:00 - 18:00</span>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
                  <component :is="Lucide.PhoneCall" class="w-4 h-4" />
                </div>
                <div>
                  <span class="font-extrabold text-gray-900 block">Teléfonos y WhatsApp</span>
                  <span class="text-gray-600 block mt-0.5">+591 700-45890 · (3) 345-6789</span>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#D1FAE5] text-[#0F766E] flex items-center justify-center shrink-0">
                  <component :is="Lucide.Share2" class="w-4 h-4" />
                </div>
                <div>
                  <span class="font-extrabold text-gray-900 block">Redes Sociales Oficiales</span>
                  <div class="flex items-center gap-3 mt-1">
                    <a
                      :href="redes?.facebook || 'https://www.facebook.com/p/Academia-De-Musica-y-Arte-Scala-100064673172866/'"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-[#0F766E] hover:underline font-bold inline-flex items-center gap-1"
                    >
                      <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                      Facebook
                    </a>
                    <a
                      :href="redes?.instagram || 'https://www.instagram.com/academia.scala/'"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-[#0F766E] hover:underline font-bold inline-flex items-center gap-1"
                    >
                      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                      Instagram
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Mapa Embebido y Botón Oficial de Google Maps -->
            <div class="rounded-xl overflow-hidden border border-[#DDEFEA] bg-gray-100 relative h-64">
              <iframe
                title="Mapa de Ubicación Academia de Música y Arte SCALA"
                src="https://www.google.com/maps?q=Academia+de+Musica+y+Arte+Scala+Santa+Cruz+Bolivia&output=embed"
                class="w-full h-full border-0"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
              ></iframe>
            </div>

            <div class="flex items-center justify-between gap-3 pt-1">
              <span class="text-xs text-gray-500">
                Ver ubicación exacta y ruta de llegada en Google Maps
              </span>
              <a
                :href="redes?.maps_url || 'https://maps.app.goo.gl/Qrk9fpKYiQyFAHyi9'"
                target="_blank"
                rel="noopener noreferrer"
                class="btn-menta text-xs !py-2"
              >
                <component :is="Lucide.Navigation" class="w-3.5 h-3.5" />
                Abrir en Google Maps
              </a>
            </div>
          </div>
        </div>

        <!-- COLUMNA DERECHA: FORMULARIO DE CONTACTO (Figura 7.6) -->
        <div id="contacto" class="lg:col-span-6 space-y-5">
          <div>
            <span class="badge-menta uppercase tracking-wider">Atención al Visitante</span>
            <h2 class="text-2xl font-black text-gray-900 mt-2">
              Formulario de Contacto y Consultas
            </h2>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">
              Envíanos tus consultas sobre inscripciones, horarios o audiciones y secretaría académica se comunicará contigo.
            </p>
          </div>

          <div class="card-menta p-6 space-y-4">
            <!-- Confirmación de envío exitoso -->
            <div
              v-if="contactoConfirmado"
              class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 space-y-1.5"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 font-extrabold text-sm text-emerald-800">
                  <component :is="Lucide.CheckCircle2" class="w-5 h-5 text-emerald-600" />
                  <span>¡Consulta enviada correctamente!</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-100 text-emerald-800">
                  {{ contactoConfirmado.codigo_ticket || 'SCALA-OK' }}
                </span>
              </div>
              <p class="text-xs text-emerald-700">
                Gracias <strong>{{ contactoConfirmado.nombre }}</strong>. Hemos registrado tu solicitud sobre <strong>{{ contactoConfirmado.curso_interes }}</strong> ({{ contactoConfirmado.fecha }}).
              </p>
            </div>

            <form @submit.prevent="enviarFormularioContacto" class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-gray-700 mb-1">Nombre Completo *</label>
                  <input
                    v-model="formContacto.nombre"
                    type="text"
                    required
                    placeholder="Ej. Andrea Vargas Roca"
                    class="input-menta text-xs py-2.5"
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 mb-1">Teléfono / WhatsApp *</label>
                  <input
                    v-model="formContacto.telefono"
                    type="text"
                    required
                    placeholder="Ej. +591 75012345"
                    class="input-menta text-xs py-2.5"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-gray-700 mb-1">Correo Electrónico *</label>
                  <input
                    v-model="formContacto.email"
                    type="email"
                    required
                    placeholder="ejemplo@correo.com"
                    class="input-menta text-xs py-2.5"
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 mb-1">Curso o Actividad de Interés</label>
                  <select v-model="formContacto.curso_interes" class="input-menta text-xs py-2.5">
                    <option value="Información General">Información General de Inscripciones</option>
                    <option v-for="c in listaCursos" :key="c.nombre" :value="c.nombre">
                      {{ c.nombre }}
                    </option>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Mensaje o Consulta *</label>
                <textarea
                  v-model="formContacto.mensaje"
                  rows="3"
                  required
                  placeholder="Indícanos la edad del postulante, horarios de preferencia o dudas sobre el curso..."
                  class="input-menta text-xs py-2.5"
                ></textarea>
              </div>

              <div class="flex items-center justify-between gap-3 pt-1">
                <span class="text-[11px] text-gray-500">
                  Al enviar aceptas nuestra
                  <Link :href="buildUrl('/politica-privacidad')" class="text-[#0F766E] font-bold hover:underline">
                    Política de Privacidad
                  </Link>.
                </span>

                <button
                  type="submit"
                  :disabled="formContacto.processing"
                  class="btn-menta text-xs !py-2.5 !px-5"
                >
                  <component :is="Lucide.Send" class="w-4 h-4" />
                  <span>{{ formContacto.processing ? 'Enviando...' : 'Enviar Consulta' }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- 7. PIE DE PÁGINA CORPORATIVO                                            -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <footer class="bg-[#0B3B36] text-teal-100 py-8 border-t border-teal-900 mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-white text-[#0F766E] flex items-center justify-center font-black">
            <component :is="Lucide.Music" class="w-5 h-5" />
          </div>
          <div>
            <p class="font-extrabold text-white">Academia de Música y Arte SCALA</p>
            <p class="text-[11px] text-teal-300">techmusic.work · Sistema de Gestión y Seguimiento Académico Predictivo</p>
          </div>
        </div>

        <div class="flex items-center gap-4 flex-wrap justify-center">
          <Link :href="buildUrl('/politica-privacidad')" class="hover:text-amber-300 transition-colors">
            Política de Privacidad
          </Link>
          <span>·</span>
          <Link :href="buildUrl('/terminos-condiciones')" class="hover:text-amber-300 transition-colors">
            Términos y Condiciones
          </Link>
          <span>·</span>
          <Link :href="buildUrl('/login')" class="hover:text-amber-300 font-bold transition-colors">
            Acceso al Sistema
          </Link>
        </div>
      </div>
    </footer>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- 8. WIDGET FLOTANTE: CHAT / AGENTE DE ATENCIÓN SCALA (Figura 7.7 - A3+B1)-->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <AgenteScalaWidget
      ref="agenteWidgetRef"
      modo="portal"
      :agente-config="agente_config"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import * as Lucide from '@lucide/vue';
import AgenteScalaWidget from '@/Components/Chat/AgenteScalaWidget.vue';

const props = defineProps({
  cursos: {
    type: Array,
    default: () => [],
  },
  metricas: {
    type: Object,
    default: () => ({}),
  },
  redes: {
    type: Object,
    default: () => ({}),
  },
  contacto_enviado: {
    type: Object,
    default: null,
  },
  agente_config: {
    type: Object,
    default: () => ({}),
  },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user || null);

const baseUrl = import.meta.env.VITE_BASE_URL || '';
const buildUrl = (path) => {
  const base = baseUrl.replace(/\/$/, '');
  const relativePath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${relativePath}`;
};

const menuMovilAbierto = ref(false);

// Carrusel Hero
const slides = [
  {
    subtitulo: 'Academia de Música y Arte',
    titulo: 'SCALA · CONSERVATORIO MODERNO',
    imagen: 'https://images.unsplash.com/photo-1511379938547-c1f69419868d?auto=format&fit=crop&w=1600&q=80',
  },
  {
    subtitulo: 'Formación Instrumental y Vocal',
    titulo: 'CLASES INDIVIDUALES Y ENSAMBLES',
    imagen: 'https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?auto=format&fit=crop&w=1600&q=80',
  },
  {
    subtitulo: 'Innovación Educativa Musical',
    titulo: 'SEGUIMIENTO ACADÉMICO PREDICTIVO',
    imagen: 'https://images.unsplash.com/photo-1507838153414-b4b713384a76?auto=format&fit=crop&w=1600&q=80',
  },
];
const slideIndex = ref(0);
const slideActual = computed(() => slides[slideIndex.value]);

function nextSlide() {
  slideIndex.value = (slideIndex.value + 1) % slides.length;
}
function prevSlide() {
  slideIndex.value = (slideIndex.value - 1 + slides.length) % slides.length;
}

// Catálogo de cursos (con fallback si la BD está vacía)
const cursosFallback = [
  {
    id: 1,
    nombre: 'Piano Básico',
    descripcion: 'Curso introductorio de piano. Lectura musical en clave de sol y fa, postura, escalas y repertorio clásico/moderno.',
    tipo_ensenanza: 'individual',
    duracion_estandar: 12,
  },
  {
    id: 2,
    nombre: 'Piano Intermedio',
    descripcion: 'Técnica avanzada, independencia digital, armonía aplicada e interpretación de sonatinas y obras populares.',
    tipo_ensenanza: 'individual',
    duracion_estandar: 12,
  },
  {
    id: 3,
    nombre: 'Guitarra Acústica',
    descripcion: 'Rasgueos, acordes, arpegios fingerpicking y repertorio folclórico boliviano e internacional.',
    tipo_ensenanza: 'grupal',
    duracion_estandar: 10,
  },
  {
    id: 4,
    nombre: 'Violín Inicial',
    descripcion: 'Postura, técnica de arco, afinación auditiva y piezas del repertorio clásico para principiantes.',
    tipo_ensenanza: 'individual',
    duracion_estandar: 12,
  },
  {
    id: 5,
    nombre: 'Batería y Percusión',
    descripcion: 'Rudimentos, coordinación de cuatro extremidades, lectura rítmica y estilos rock, jazz y ritmos latinos.',
    tipo_ensenanza: 'individual',
    duracion_estandar: 10,
  },
  {
    id: 6,
    nombre: 'Técnica Vocal',
    descripcion: 'Respiración costo-diafragmática, vocalización, impostación, higiene vocal y repertorio solista.',
    tipo_ensenanza: 'individual',
    duracion_estandar: 12,
  },
];

const listaCursos = computed(() => {
  return props.cursos && props.cursos.length > 0 ? props.cursos : cursosFallback;
});

const filtroModalidad = ref('TODOS');
const busquedaCurso = ref('');

const cursosFiltrados = computed(() => {
  return listaCursos.value.filter((c) => {
    if (filtroModalidad.value !== 'TODOS' && c.tipo_ensenanza !== filtroModalidad.value) {
      return false;
    }
    if (busquedaCurso.value.trim()) {
      const q = busquedaCurso.value.toLowerCase().trim();
      return (
        (c.nombre || '').toLowerCase().includes(q) ||
        (c.descripcion || '').toLowerCase().includes(q)
      );
    }
    return true;
  });
});

function irAServicios() {
  menuMovilAbierto.value = false;
  const el = document.getElementById('servicios');
  if (el) el.scrollIntoView({ behavior: 'smooth' });
}

function getIconoCurso(nombre) {
  const n = (nombre || '').toLowerCase();
  if (n.includes('piano')) return Lucide.Piano || Lucide.Music;
  if (n.includes('guitarra')) return Lucide.Guitar || Lucide.Music2;
  if (n.includes('vocal') || n.includes('canto')) return Lucide.MicVocal || Lucide.Mic;
  if (n.includes('batería') || n.includes('percusion')) return Lucide.Drum || Lucide.Disc;
  return Lucide.Music4 || Lucide.Music;
}

const actividades = [
  {
    titulo: 'Audiciones y Recitales de Gala',
    descripcion: 'Presentaciones escénicas periódicas para desarrollar seguridad interpretativa frente al público y la familia.',
    icono: Lucide.Award,
  },
  {
    titulo: 'Práctica en Salas Acustizadas',
    descripcion: 'Aulas equipadas con pianos verticales y de cola, baterías acústicas y sistemas de monitoreo.',
    icono: Lucide.Headphones,
  },
  {
    titulo: 'Ficha de Seguimiento 360°',
    descripcion: 'Acceso transparente para alumnos y padres al historial de asistencias, libreta de notas y bitácora docente.',
    icono: Lucide.FileBadge,
  },
  {
    titulo: 'Alerta Temprana con IA (XAI)',
    descripcion: 'Monitoreo inteligente del progreso estudiantil para programar tutorías de refuerzo oportunas.',
    icono: Lucide.BrainCircuit,
  },
];

// Formulario de Contacto (Figura 7.6)
const contactoLocalConfirmado = ref(null);
const contactoConfirmado = computed(() => props.contacto_enviado || contactoLocalConfirmado.value);

const formContacto = useForm({
  nombre: '',
  telefono: '',
  email: '',
  curso_interes: 'Piano Básico',
  mensaje: '',
});

function seleccionarCursoContacto(nombreCurso) {
  formContacto.curso_interes = nombreCurso;
}

function enviarFormularioContacto() {
  formContacto.post(buildUrl('/contacto'), {
    preserveScroll: true,
    onSuccess: () => {
      contactoLocalConfirmado.value = {
        nombre: formContacto.nombre,
        curso_interes: formContacto.curso_interes,
        fecha: 'Justo ahora',
        codigo_ticket: 'SCALA-' + Math.floor(1000 + Math.random() * 9000),
      };
      formContacto.reset('nombre', 'telefono', 'email', 'mensaje');
    },
  });
}

// Referencia al Widget Agente Virtual de Atención (Figura 7.7 - Opción A3 + B1)
const agenteWidgetRef = ref(null);

function abrirAgenteChat() {
  if (agenteWidgetRef.value && typeof agenteWidgetRef.value.abrir === 'function') {
    agenteWidgetRef.value.abrir();
  }
}
</script>
