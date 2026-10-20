<template>
  <nav class="main-navbar">
    <div class="nav-container">
      <!-- Marca Robledal -->
      <div class="nav-brand">
        <RobledalLogo :size="32" :is-dark="themeStore.isDark" />
      </div>

      <!-- Menú Desktop Filtrado Dinámicamente por Permisos de Vistas -->
      <div class="nav-tabs desktop-only">
        <!-- 0. Dashboard Ejecutivo -->
        <button 
          v-if="authStore.puedeVerVista('dashboard')"
          :class="vistaActual === 'dashboard' ? 'nav-tab-active' : 'nav-tab'"
          @click="irA('dashboard')"
        >
          <LayoutDashboard :size="15" />
          <span>Dashboard</span>
        </button>

        <!-- 1. Operación Garita -->
        <button 
          v-if="authStore.puedeVerVista('garita')"
          :class="vistaActual === 'garita' ? 'nav-tab-active' : 'nav-tab'"
          @click="irA('garita')"
        >
          <ShieldAlert :size="15" />
          <span>Garita</span>
        </button>

        <!-- 1.1 Portal Residente (Mi Hogar & Visitas QR) -->
        <button 
          v-if="authStore.puedeVerVista('portal.residente')"
          :class="vistaActual === 'portal.residente' ? 'nav-tab-active' : 'nav-tab'"
          @click="irA('portal.residente')"
        >
          <Home :size="15" />
          <span>Mi Hogar</span>
        </button>

        <!-- 2. Dropdown ADMINISTRACIÓN -->
        <div 
          v-if="authStore.puedeVerVista('admin.urbanizaciones') || authStore.puedeVerVista('admin.inmuebles')"
          class="dropdown-wrap" 
          ref="adminRef"
        >
          <button 
            :class="isAdminActive ? 'nav-tab-active' : 'nav-tab'" 
            class="nav-tab-button"
            @click.stop="toggleAdmin"
          >
            <Building2 :size="15" />
            <span>Administración</span>
            <ChevronDown :size="13" class="chevron-icon" :class="{ 'rotate-180': openAdmin }" />
          </button>

          <transition name="dropdown-anim">
            <div v-show="openAdmin" class="dropdown-menu">
              <button 
                v-if="authStore.puedeVerVista('admin.urbanizaciones')"
                :class="{ 'drop-active': vistaActual === 'urbanizaciones' }"
                @click.stop="seleccionarSubvista('urbanizaciones')"
                class="drop-item"
              >
                <Building2 :size="16" class="drop-icon" />
                <div class="drop-text">
                  <span class="drop-title">Urbanizaciones</span>
                  <span class="drop-desc">Parametrización de sedes y reglas</span>
                </div>
              </button>

              <button 
                v-if="authStore.puedeVerVista('admin.inmuebles')"
                :class="{ 'drop-active': vistaActual === 'inmuebles' }"
                @click.stop="seleccionarSubvista('inmuebles')"
                class="drop-item"
              >
                <Home :size="16" class="drop-icon" />
                <div class="drop-text">
                  <span class="drop-title">Inmuebles & Residentes</span>
                  <span class="drop-desc">Manzanas, villas y habitantes</span>
                </div>
              </button>

              <button 
                :class="{ 'drop-active': vistaActual === 'alicuotas' }"
                @click.stop="seleccionarSubvista('alicuotas')"
                class="drop-item"
              >
                <DollarSign :size="16" class="drop-icon text-emerald-400" />
                <div class="drop-text">
                  <span class="drop-title">Tesorería & Alícuotas</span>
                  <span class="drop-desc">Emisión masiva, cobros y cartera</span>
                </div>
              </button>

              <button 
                :class="{ 'drop-active': vistaActual === 'areas-sociales' }"
                @click.stop="seleccionarSubvista('areas-sociales')"
                class="drop-item"
              >
                <CalendarDays :size="16" class="drop-icon text-cyan-400" />
                <div class="drop-text">
                  <span class="drop-title">Áreas Sociales & Reservas</span>
                  <span class="drop-desc">Canchas, BBQ, aforos y agenda</span>
                </div>
              </button>

              <button 
                :class="{ 'drop-active': vistaActual === 'votaciones' }"
                @click.stop="seleccionarSubvista('votaciones')"
                class="drop-item"
              >
                <Vote :size="16" class="drop-icon text-purple-400" />
                <div class="drop-text">
                  <span class="drop-title">Comunicados & Votaciones</span>
                  <span class="drop-desc">Asambleas, quórum y sondeos</span>
                </div>
              </button>

              <button 
                :class="{ 'drop-active': vistaActual === 'biometria' }"
                @click.stop="seleccionarSubvista('biometria')"
                class="drop-item"
              >
                <ScanFace :size="16" class="drop-icon text-cyan-400" />
                <div class="drop-text">
                  <span class="drop-title">Biometría ISAPI Hikvision</span>
                  <span class="drop-desc">Rostro, huellas y export iVMS</span>
                </div>
              </button>

              <button 
                :class="{ 'drop-active': vistaActual === 'mantenimiento' }"
                @click.stop="seleccionarSubvista('mantenimiento')"
                class="drop-item"
              >
                <Wrench :size="16" class="drop-icon text-amber-400" />
                <div class="drop-text">
                  <span class="drop-title">Mantenimiento & Facility</span>
                  <span class="drop-desc">Activos, bombas, generador y órdenes</span>
                </div>
              </button>

              <button 
                v-if="authStore.puedeVerVista('admin.personas') || authStore.user?.es_superadmin"
                :class="{ 'drop-active': vistaActual === 'personas' }"
                @click.stop="seleccionarSubvista('personas')"
                class="drop-item"
              >
                <Contact :size="16" class="drop-icon" />
                <div class="drop-text">
                  <span class="drop-title">Padrón de Personas</span>
                  <span class="drop-desc">Directorio de visitantes y autocompletado</span>
                </div>
              </button>
            </div>
          </transition>
        </div>

        <!-- 3. Dropdown SEGURIDAD & AUDITORÍA -->
        <div 
          v-if="authStore.puedeVerVista('sec.usuarios') || authStore.puedeVerVista('sec.vistas') || authStore.puedeVerVista('sec.roles') || authStore.puedeVerVista('sec.auditoria')"
          class="dropdown-wrap" 
          ref="segRef"
        >
          <button 
            :class="isSegActive ? 'nav-tab-active' : 'nav-tab'" 
            class="nav-tab-button"
            @click.stop="toggleSeg"
          >
            <KeyRound :size="15" />
            <span>Seguridad</span>
            <ChevronDown :size="13" class="chevron-icon" :class="{ 'rotate-180': openSeg }" />
          </button>

          <transition name="dropdown-anim">
            <div v-show="openSeg" class="dropdown-menu">
              <button 
                v-if="authStore.puedeVerVista('sec.usuarios')"
                :class="{ 'drop-active': vistaActual === 'usuarios' }"
                @click.stop="seleccionarSubvista('usuarios')"
                class="drop-item"
              >
                <Users :size="16" class="drop-icon" />
                <div class="drop-text">
                  <span class="drop-title">Usuarios & Cuentas</span>
                  <span class="drop-desc">Gestión de accesos y credenciales</span>
                </div>
              </button>

              <button 
                v-if="authStore.puedeVerVista('sec.vistas')"
                :class="{ 'drop-active': vistaActual === 'vistas' }"
                @click.stop="seleccionarSubvista('vistas')"
                class="drop-item"
              >
                <LayoutGrid :size="16" class="drop-icon" />
                <div class="drop-text">
                  <span class="drop-title">Administración de Vistas</span>
                  <span class="drop-desc">Asignar pantallas a cada rol</span>
                </div>
              </button>

              <button 
                v-if="authStore.puedeVerVista('sec.roles')"
                :class="{ 'drop-active': vistaActual === 'roles' }"
                @click.stop="seleccionarSubvista('roles')"
                class="drop-item"
              >
                <ShieldCheck :size="16" class="drop-icon" />
                <div class="drop-text">
                  <span class="drop-title">Matriz de Permisos (RBAC)</span>
                  <span class="drop-desc">Permisos CRUD + Print en 5D</span>
                </div>
              </button>

              <button 
                v-if="authStore.puedeVerVista('sec.auditoria')"
                :class="{ 'drop-active': vistaActual === 'auditoria' }"
                @click.stop="seleccionarSubvista('auditoria')"
                class="drop-item"
              >
                <History :size="16" class="drop-icon" />
                <div class="drop-text">
                  <span class="drop-title">Bitácora CDC (Auditoría)</span>
                  <span class="drop-desc">Trazabilidad de cambios y diffs</span>
                </div>
              </button>
            </div>
          </transition>
        </div>

        <!-- 4. Reportes -->
        <button 
          v-if="authStore.puedeVerVista('rep.accesos')"
          :class="vistaActual === 'reportes' ? 'nav-tab-active' : 'nav-tab'"
          @click="irA('reportes')"
        >
          <BarChart3 :size="15" />
          <span>Reportes</span>
        </button>
      </div>

      <!-- Controles de Usuario y Temas Desktop -->
      <div class="nav-actions desktop-only">
        <!-- Selector Rápido de Temas de Robledal: Botón Icono con Tooltip -->
        <div class="theme-toggle-wrap">
          <button 
            @click="themeStore.toggleRobledalMode()" 
            class="btn-theme-toggle" 
            aria-label="Alternar Modo Claro / Oscuro"
          >
            <Sun v-if="themeStore.isDark" :size="18" class="theme-icon" />
            <Moon v-else :size="18" class="theme-icon" />
          </button>
          <div class="theme-tooltip">
            {{ themeStore.isDark ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro' }}
          </div>
        </div>

        <!-- Menú Dropdown de Usuario con Perfil y Contraseña -->
        <div class="dropdown-wrap" ref="userMenuRef">
          <div class="user-pill user-pill-clickable" @click="toggleUserMenu" title="Opciones de Cuenta">
            <div class="user-avatar">
              {{ userIniciales }}
            </div>
            <div class="user-info">
              <span class="user-name flex items-center gap-1">
                <span>{{ authStore.user?.name || 'Usuario' }}</span>
                <ChevronDown :size="12" class="chevron-icon" :class="{ 'rotate-180': openUserMenu }" />
              </span>
              <span class="user-role">{{ authStore.user?.es_superadmin ? 'SuperAdmin Global' : 'Usuario' }}</span>
            </div>
          </div>

          <transition name="dropdown-anim">
            <div v-show="openUserMenu" class="dropdown-menu dropdown-user-menu">
              <div class="user-menu-header">
                <div class="text-xs font-bold text-[var(--text-primary)] truncate">{{ authStore.user?.name }}</div>
                <div class="text-[11px] text-[var(--text-muted)] truncate">{{ authStore.user?.email }}</div>
              </div>

              <button @click="abrirEditarPerfil" class="drop-item">
                <User :size="15" class="drop-icon text-[var(--color-primary)]" />
                <div class="drop-text">
                  <span class="drop-title">Editar Mi Perfil</span>
                  <span class="drop-desc">Nombre, correo y teléfono</span>
                </div>
              </button>

              <button @click="abrirCambiarPassword" class="drop-item">
                <Lock :size="15" class="drop-icon text-amber-400" />
                <div class="drop-text">
                  <span class="drop-title">Cambiar Contraseña</span>
                  <span class="drop-desc">Actualizar clave de acceso</span>
                </div>
              </button>

              <div class="drop-divider"></div>

              <button @click="authStore.logout()" class="drop-item drop-item-danger">
                <LogOut :size="15" class="drop-icon text-red-400" />
                <div class="drop-text">
                  <span class="drop-title text-red-400">Cerrar Sesión</span>
                  <span class="drop-desc">Finalizar jornada de trabajo</span>
                </div>
              </button>
            </div>
          </transition>
        </div>
      </div>

      <!-- Botón Menú Hamburguesa Móvil -->
      <div class="mobile-actions mobile-only">
        <button @click="themeStore.toggleRobledalMode()" class="btn-icon mr-2" title="Alternar Tema">
          <Sun v-if="themeStore.isDark" :size="18" />
          <Moon v-else :size="18" />
        </button>
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="btn-burger" :aria-expanded="mobileMenuOpen">
          <X v-if="mobileMenuOpen" :size="22" />
          <Menu v-else :size="22" />
        </button>
      </div>
    </div>

    <!-- Cajón Drawer Móvil (Filtrado por Permisos de Rol) -->
    <transition name="drawer">
      <div v-if="mobileMenuOpen" class="mobile-drawer">
        <div class="drawer-header">
          <div class="user-pill-mobile">
            <div class="user-avatar">{{ userIniciales }}</div>
            <div>
              <div class="font-semibold text-xs text-[var(--text-primary)]">{{ authStore.user?.name }}</div>
              <div class="text-[10px] text-[var(--text-secondary)]">{{ authStore.user?.email }}</div>
            </div>
          </div>
        </div>

        <div class="drawer-links">
          <!-- Operación -->
          <template v-if="authStore.puedeVerVista('dashboard') || authStore.puedeVerVista('garita') || authStore.puedeVerVista('portal.residente')">
            <div class="drawer-group-title">Operación</div>
            <button v-if="authStore.puedeVerVista('dashboard')" @click="seleccionarSubvista('dashboard')" :class="{ 'drawer-link-active': vistaActual === 'dashboard' }" class="drawer-link">
              <LayoutDashboard :size="16" /> <span>Dashboard Ejecutivo</span>
            </button>
            <button v-if="authStore.puedeVerVista('garita')" @click="seleccionarSubvista('garita')" :class="{ 'drawer-link-active': vistaActual === 'garita' }" class="drawer-link">
              <ShieldAlert :size="16" /> <span>Control de Garita</span>
            </button>
            <button v-if="authStore.puedeVerVista('portal.residente')" @click="seleccionarSubvista('portal.residente')" :class="{ 'drawer-link-active': vistaActual === 'portal.residente' }" class="drawer-link">
              <Home :size="16" /> <span>Mi Hogar & Visitas QR</span>
            </button>
          
    <!-- MODAL EDITAR MI PERFIL -->
    <div v-if="mostrarModalPerfil" class="modal-overlay" @click.self="mostrarModalPerfil = false">
      <div class="modal-box modal-box-perfil">
        <div class="modal-header">
          <div class="flex items-center gap-2.5">
            <div class="modal-icon-wrap">
              <User :size="18" class="text-[var(--color-primary)]" />
            </div>
            <div>
              <h3 class="modal-title">Editar Mi Perfil</h3>
              <p class="text-xs text-[var(--text-muted)]">Información personal y de contacto del usuario</p>
            </div>
          </div>
          <button @click="mostrarModalPerfil = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarPerfil" class="space-y-4">
          <div v-if="formPerfil.identificacion" class="input-block">
            <label>Cédula / Identificación (Padrón)</label>
            <input v-model="formPerfil.identificacion" class="form-input opacity-70" disabled />
          </div>

          <div class="input-block">
            <label>Nombres y Apellidos Completos *</label>
            <input v-model="formPerfil.name" class="form-input" required />
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Correo Electrónico *</label>
              <input v-model="formPerfil.email" type="email" class="form-input" required />
            </div>
            <div class="input-block">
              <label>Teléfono Móvil</label>
              <input v-model="formPerfil.telefono" placeholder="0999999999" class="form-input" />
            </div>
          </div>

          <div class="modal-actions pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="mostrarModalPerfil = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoPerfil" class="btn-primary">
              {{ guardandoPerfil ? 'Guardando...' : 'Actualizar Perfil' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL CAMBIAR CONTRASEÑA -->
    <div v-if="mostrarModalPassword" class="modal-overlay" @click.self="mostrarModalPassword = false">
      <div class="modal-box modal-box-perfil">
        <div class="modal-header">
          <div class="flex items-center gap-2.5">
            <div class="modal-icon-wrap bg-amber-500/10 border-amber-500/30">
              <Lock :size="18" class="text-amber-400" />
            </div>
            <div>
              <h3 class="modal-title">Cambiar Contraseña</h3>
              <p class="text-xs text-[var(--text-muted)]">Actualiza tu clave de acceso al sistema</p>
            </div>
          </div>
          <button @click="mostrarModalPassword = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarPassword" class="space-y-4">
          <div class="input-block">
            <label>Contraseña Actual *</label>
            <input v-model="formPassword.password_actual" type="password" placeholder="Tu clave actual" class="form-input" required />
          </div>

          <div class="input-block">
            <label>Nueva Contraseña *</label>
            <input v-model="formPassword.nuevo_password" type="password" placeholder="Mínimo 6 caracteres" class="form-input" required minlength="6" />
          </div>

          <div class="input-block">
            <label>Confirmar Nueva Contraseña *</label>
            <input v-model="formPassword.nuevo_password_confirmation" type="password" placeholder="Repite la nueva clave" class="form-input" required minlength="6" />
          </div>

          <div class="modal-actions pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="mostrarModalPassword = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoPassword" class="btn-primary">
              {{ guardandoPassword ? 'Modificando...' : 'Modificar Contraseña' }}
            </button>
          </div>
        </form>
      </div>
    </div>

</template>

          <!-- Administración -->
          <template v-if="authStore.puedeVerVista('admin.urbanizaciones') || authStore.puedeVerVista('admin.inmuebles')">
            <div class="drawer-group-title">Administración</div>
            <button v-if="authStore.puedeVerVista('admin.urbanizaciones')" @click="seleccionarSubvista('urbanizaciones')" :class="{ 'drawer-link-active': vistaActual === 'urbanizaciones' }" class="drawer-link">
              <Building2 :size="16" /> <span>Urbanizaciones</span>
            </button>
            <button v-if="authStore.puedeVerVista('admin.inmuebles')" @click="seleccionarSubvista('inmuebles')" :class="{ 'drawer-link-active': vistaActual === 'inmuebles' }" class="drawer-link">
              <Home :size="16" /> <span>Inmuebles & Residentes</span>
            </button>
            <button v-if="authStore.puedeVerVista('admin.personas') || authStore.user?.es_superadmin" @click="seleccionarSubvista('personas')" :class="{ 'drawer-link-active': vistaActual === 'personas' }" class="drawer-link">
              <Contact :size="16" /> <span>Padrón de Personas</span>
            </button>
          
    <!-- MODAL EDITAR MI PERFIL -->
    <div v-if="mostrarModalPerfil" class="modal-overlay" @click.self="mostrarModalPerfil = false">
      <div class="modal-box modal-box-perfil">
        <div class="modal-header">
          <div class="flex items-center gap-2.5">
            <div class="modal-icon-wrap">
              <User :size="18" class="text-[var(--color-primary)]" />
            </div>
            <div>
              <h3 class="modal-title">Editar Mi Perfil</h3>
              <p class="text-xs text-[var(--text-muted)]">Información personal y de contacto del usuario</p>
            </div>
          </div>
          <button @click="mostrarModalPerfil = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarPerfil" class="space-y-4">
          <div v-if="formPerfil.identificacion" class="input-block">
            <label>Cédula / Identificación (Padrón)</label>
            <input v-model="formPerfil.identificacion" class="form-input opacity-70" disabled />
          </div>

          <div class="input-block">
            <label>Nombres y Apellidos Completos *</label>
            <input v-model="formPerfil.name" class="form-input" required />
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Correo Electrónico *</label>
              <input v-model="formPerfil.email" type="email" class="form-input" required />
            </div>
            <div class="input-block">
              <label>Teléfono Móvil</label>
              <input v-model="formPerfil.telefono" placeholder="0999999999" class="form-input" />
            </div>
          </div>

          <div class="modal-actions pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="mostrarModalPerfil = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoPerfil" class="btn-primary">
              {{ guardandoPerfil ? 'Guardando...' : 'Actualizar Perfil' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL CAMBIAR CONTRASEÑA -->
    <div v-if="mostrarModalPassword" class="modal-overlay" @click.self="mostrarModalPassword = false">
      <div class="modal-box modal-box-perfil">
        <div class="modal-header">
          <div class="flex items-center gap-2.5">
            <div class="modal-icon-wrap bg-amber-500/10 border-amber-500/30">
              <Lock :size="18" class="text-amber-400" />
            </div>
            <div>
              <h3 class="modal-title">Cambiar Contraseña</h3>
              <p class="text-xs text-[var(--text-muted)]">Actualiza tu clave de acceso al sistema</p>
            </div>
          </div>
          <button @click="mostrarModalPassword = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarPassword" class="space-y-4">
          <div class="input-block">
            <label>Contraseña Actual *</label>
            <input v-model="formPassword.password_actual" type="password" placeholder="Tu clave actual" class="form-input" required />
          </div>

          <div class="input-block">
            <label>Nueva Contraseña *</label>
            <input v-model="formPassword.nuevo_password" type="password" placeholder="Mínimo 6 caracteres" class="form-input" required minlength="6" />
          </div>

          <div class="input-block">
            <label>Confirmar Nueva Contraseña *</label>
            <input v-model="formPassword.nuevo_password_confirmation" type="password" placeholder="Repite la nueva clave" class="form-input" required minlength="6" />
          </div>

          <div class="modal-actions pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="mostrarModalPassword = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoPassword" class="btn-primary">
              {{ guardandoPassword ? 'Modificando...' : 'Modificar Contraseña' }}
            </button>
          </div>
        </form>
      </div>
    </div>

</template>

          <!-- Seguridad -->
          <template v-if="authStore.puedeVerVista('sec.usuarios') || authStore.puedeVerVista('sec.vistas') || authStore.puedeVerVista('sec.roles') || authStore.puedeVerVista('sec.auditoria')">
            <div class="drawer-group-title">Seguridad</div>
            <button v-if="authStore.puedeVerVista('sec.usuarios')" @click="seleccionarSubvista('usuarios')" :class="{ 'drawer-link-active': vistaActual === 'usuarios' }" class="drawer-link">
              <Users :size="16" /> <span>Usuarios & Cuentas</span>
            </button>
            <button v-if="authStore.puedeVerVista('sec.vistas')" @click="seleccionarSubvista('vistas')" :class="{ 'drawer-link-active': vistaActual === 'vistas' }" class="drawer-link">
              <LayoutGrid :size="16" /> <span>Administración de Vistas</span>
            </button>
            <button v-if="authStore.puedeVerVista('sec.roles')" @click="seleccionarSubvista('roles')" :class="{ 'drawer-link-active': vistaActual === 'roles' }" class="drawer-link">
              <ShieldCheck :size="16" /> <span>Matriz RBAC</span>
            </button>
            <button v-if="authStore.puedeVerVista('sec.auditoria')" @click="seleccionarSubvista('auditoria')" :class="{ 'drawer-link-active': vistaActual === 'auditoria' }" class="drawer-link">
              <History :size="16" /> <span>Bitácora CDC</span>
            </button>
          
    <!-- MODAL EDITAR MI PERFIL -->
    <div v-if="mostrarModalPerfil" class="modal-overlay" @click.self="mostrarModalPerfil = false">
      <div class="modal-box modal-box-perfil">
        <div class="modal-header">
          <div class="flex items-center gap-2.5">
            <div class="modal-icon-wrap">
              <User :size="18" class="text-[var(--color-primary)]" />
            </div>
            <div>
              <h3 class="modal-title">Editar Mi Perfil</h3>
              <p class="text-xs text-[var(--text-muted)]">Información personal y de contacto del usuario</p>
            </div>
          </div>
          <button @click="mostrarModalPerfil = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarPerfil" class="space-y-4">
          <div v-if="formPerfil.identificacion" class="input-block">
            <label>Cédula / Identificación (Padrón)</label>
            <input v-model="formPerfil.identificacion" class="form-input opacity-70" disabled />
          </div>

          <div class="input-block">
            <label>Nombres y Apellidos Completos *</label>
            <input v-model="formPerfil.name" class="form-input" required />
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Correo Electrónico *</label>
              <input v-model="formPerfil.email" type="email" class="form-input" required />
            </div>
            <div class="input-block">
              <label>Teléfono Móvil</label>
              <input v-model="formPerfil.telefono" placeholder="0999999999" class="form-input" />
            </div>
          </div>

          <div class="modal-actions pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="mostrarModalPerfil = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoPerfil" class="btn-primary">
              {{ guardandoPerfil ? 'Guardando...' : 'Actualizar Perfil' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL CAMBIAR CONTRASEÑA -->
    <div v-if="mostrarModalPassword" class="modal-overlay" @click.self="mostrarModalPassword = false">
      <div class="modal-box modal-box-perfil">
        <div class="modal-header">
          <div class="flex items-center gap-2.5">
            <div class="modal-icon-wrap bg-amber-500/10 border-amber-500/30">
              <Lock :size="18" class="text-amber-400" />
            </div>
            <div>
              <h3 class="modal-title">Cambiar Contraseña</h3>
              <p class="text-xs text-[var(--text-muted)]">Actualiza tu clave de acceso al sistema</p>
            </div>
          </div>
          <button @click="mostrarModalPassword = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarPassword" class="space-y-4">
          <div class="input-block">
            <label>Contraseña Actual *</label>
            <input v-model="formPassword.password_actual" type="password" placeholder="Tu clave actual" class="form-input" required />
          </div>

          <div class="input-block">
            <label>Nueva Contraseña *</label>
            <input v-model="formPassword.nuevo_password" type="password" placeholder="Mínimo 6 caracteres" class="form-input" required minlength="6" />
          </div>

          <div class="input-block">
            <label>Confirmar Nueva Contraseña *</label>
            <input v-model="formPassword.nuevo_password_confirmation" type="password" placeholder="Repite la nueva clave" class="form-input" required minlength="6" />
          </div>

          <div class="modal-actions pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="mostrarModalPassword = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoPassword" class="btn-primary">
              {{ guardandoPassword ? 'Modificando...' : 'Modificar Contraseña' }}
            </button>
          </div>
        </form>
      </div>
    </div>

</template>

          <!-- Análisis -->
          <template v-if="authStore.puedeVerVista('rep.accesos')">
            <div class="drawer-group-title">Análisis</div>
            <button @click="seleccionarSubvista('reportes')" :class="{ 'drawer-link-active': vistaActual === 'reportes' }" class="drawer-link">
              <BarChart3 :size="16" /> <span>Reportes</span>
            </button>
          
    <!-- MODAL EDITAR MI PERFIL -->
    <div v-if="mostrarModalPerfil" class="modal-overlay" @click.self="mostrarModalPerfil = false">
      <div class="modal-box modal-box-perfil">
        <div class="modal-header">
          <div class="flex items-center gap-2.5">
            <div class="modal-icon-wrap">
              <User :size="18" class="text-[var(--color-primary)]" />
            </div>
            <div>
              <h3 class="modal-title">Editar Mi Perfil</h3>
              <p class="text-xs text-[var(--text-muted)]">Información personal y de contacto del usuario</p>
            </div>
          </div>
          <button @click="mostrarModalPerfil = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarPerfil" class="space-y-4">
          <div v-if="formPerfil.identificacion" class="input-block">
            <label>Cédula / Identificación (Padrón)</label>
            <input v-model="formPerfil.identificacion" class="form-input opacity-70" disabled />
          </div>

          <div class="input-block">
            <label>Nombres y Apellidos Completos *</label>
            <input v-model="formPerfil.name" class="form-input" required />
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Correo Electrónico *</label>
              <input v-model="formPerfil.email" type="email" class="form-input" required />
            </div>
            <div class="input-block">
              <label>Teléfono Móvil</label>
              <input v-model="formPerfil.telefono" placeholder="0999999999" class="form-input" />
            </div>
          </div>

          <div class="modal-actions pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="mostrarModalPerfil = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoPerfil" class="btn-primary">
              {{ guardandoPerfil ? 'Guardando...' : 'Actualizar Perfil' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL CAMBIAR CONTRASEÑA -->
    <div v-if="mostrarModalPassword" class="modal-overlay" @click.self="mostrarModalPassword = false">
      <div class="modal-box modal-box-perfil">
        <div class="modal-header">
          <div class="flex items-center gap-2.5">
            <div class="modal-icon-wrap bg-amber-500/10 border-amber-500/30">
              <Lock :size="18" class="text-amber-400" />
            </div>
            <div>
              <h3 class="modal-title">Cambiar Contraseña</h3>
              <p class="text-xs text-[var(--text-muted)]">Actualiza tu clave de acceso al sistema</p>
            </div>
          </div>
          <button @click="mostrarModalPassword = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarPassword" class="space-y-4">
          <div class="input-block">
            <label>Contraseña Actual *</label>
            <input v-model="formPassword.password_actual" type="password" placeholder="Tu clave actual" class="form-input" required />
          </div>

          <div class="input-block">
            <label>Nueva Contraseña *</label>
            <input v-model="formPassword.nuevo_password" type="password" placeholder="Mínimo 6 caracteres" class="form-input" required minlength="6" />
          </div>

          <div class="input-block">
            <label>Confirmar Nueva Contraseña *</label>
            <input v-model="formPassword.nuevo_password_confirmation" type="password" placeholder="Repite la nueva clave" class="form-input" required minlength="6" />
          </div>

          <div class="modal-actions pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="mostrarModalPassword = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoPassword" class="btn-primary">
              {{ guardandoPassword ? 'Modificando...' : 'Modificar Contraseña' }}
            </button>
          </div>
        </form>
      </div>
    </div>

</template>
        </div>

        <div class="drawer-footer">
          <button @click="authStore.logout()" class="btn-logout-mobile">
            <LogOut :size="16" /> Cerrar Sesión
          </button>
        </div>
      </div>
    </transition>
  </nav>

    <!-- MODAL EDITAR MI PERFIL -->
    <div v-if="mostrarModalPerfil" class="modal-overlay" @click.self="mostrarModalPerfil = false">
      <div class="modal-box modal-box-perfil">
        <div class="modal-header">
          <div class="flex items-center gap-2.5">
            <div class="modal-icon-wrap">
              <User :size="18" class="text-[var(--color-primary)]" />
            </div>
            <div>
              <h3 class="modal-title">Editar Mi Perfil</h3>
              <p class="text-xs text-[var(--text-muted)]">Información personal y de contacto del usuario</p>
            </div>
          </div>
          <button @click="mostrarModalPerfil = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarPerfil" class="space-y-4">
          <div v-if="formPerfil.identificacion" class="input-block">
            <label>Cédula / Identificación (Padrón)</label>
            <input v-model="formPerfil.identificacion" class="form-input opacity-70" disabled />
          </div>

          <div class="input-block">
            <label>Nombres y Apellidos Completos *</label>
            <input v-model="formPerfil.name" class="form-input" required />
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Correo Electrónico *</label>
              <input v-model="formPerfil.email" type="email" class="form-input" required />
            </div>
            <div class="input-block">
              <label>Teléfono Móvil</label>
              <input v-model="formPerfil.telefono" placeholder="0999999999" class="form-input" />
            </div>
          </div>

          <div class="modal-actions pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="mostrarModalPerfil = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoPerfil" class="btn-primary">
              {{ guardandoPerfil ? 'Guardando...' : 'Actualizar Perfil' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL CAMBIAR CONTRASEÑA -->
    <div v-if="mostrarModalPassword" class="modal-overlay" @click.self="mostrarModalPassword = false">
      <div class="modal-box modal-box-perfil">
        <div class="modal-header">
          <div class="flex items-center gap-2.5">
            <div class="modal-icon-wrap bg-amber-500/10 border-amber-500/30">
              <Lock :size="18" class="text-amber-400" />
            </div>
            <div>
              <h3 class="modal-title">Cambiar Contraseña</h3>
              <p class="text-xs text-[var(--text-muted)]">Actualiza tu clave de acceso al sistema</p>
            </div>
          </div>
          <button @click="mostrarModalPassword = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarPassword" class="space-y-4">
          <div class="input-block">
            <label>Contraseña Actual *</label>
            <input v-model="formPassword.password_actual" type="password" placeholder="Tu clave actual" class="form-input" required />
          </div>

          <div class="input-block">
            <label>Nueva Contraseña *</label>
            <input v-model="formPassword.nuevo_password" type="password" placeholder="Mínimo 6 caracteres" class="form-input" required minlength="6" />
          </div>

          <div class="input-block">
            <label>Confirmar Nueva Contraseña *</label>
            <input v-model="formPassword.nuevo_password_confirmation" type="password" placeholder="Repite la nueva clave" class="form-input" required minlength="6" />
          </div>

          <div class="modal-actions pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="mostrarModalPassword = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoPassword" class="btn-primary">
              {{ guardandoPassword ? 'Modificando...' : 'Modificar Contraseña' }}
            </button>
          </div>
        </form>
      </div>
    </div>

</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import { useThemeStore } from '../stores/theme';
import RobledalLogo from './RobledalLogo.vue';
import { 
  LayoutDashboard, ShieldAlert, Building2, DollarSign, CalendarDays, Vote, ScanFace, Wrench, KeyRound, BarChart3, Sun, Moon, LogOut, 
  ChevronDown, Home, Users, Contact, LayoutGrid, ShieldCheck, History, Menu, X,
  User, Lock, Edit3
} from 'lucide-vue-next';

const props = defineProps({
  vistaActual: { type: String, default: 'garita' }
});

const emit = defineEmits(['cambiarVista']);

const authStore = useAuthStore();
const themeStore = useThemeStore();

const openAdmin = ref(false);
const openSeg = ref(false);
const openUserMenu = ref(false);
const userMenuRef = ref(null);

// Modales de Usuario
const mostrarModalPerfil = ref(false);
const guardandoPerfil = ref(false);
const formPerfil = reactive({
  name: '',
  email: '',
  telefono: '',
  identificacion: ''
});

const mostrarModalPassword = ref(false);
const guardandoPassword = ref(false);
const formPassword = reactive({
  password_actual: '',
  nuevo_password: '',
  nuevo_password_confirmation: ''
});

const toggleUserMenu = () => {
  openUserMenu.value = !openUserMenu.value;
  if (openUserMenu.value) {
    openAdmin.value = false;
    openSeg.value = false;
  }
};

const abrirEditarPerfil = () => {
  openUserMenu.value = false;
  formPerfil.name = authStore.user?.name || '';
  formPerfil.email = authStore.user?.email || '';
  formPerfil.telefono = authStore.user?.telefono || '';
  formPerfil.identificacion = authStore.user?.identificacion || '';
  mostrarModalPerfil.value = true;
};

const guardarPerfil = async () => {
  guardandoPerfil.value = true;
  try {
    const res = await axios.put('/api/auth/perfil', formPerfil);
    if (res.data.success) {
      alert('Perfil actualizado exitosamente.');
      authStore.user.name = res.data.user.name;
      authStore.user.email = res.data.user.email;
      authStore.user.telefono = res.data.user.telefono;
      localStorage.setItem('residia_user', JSON.stringify(authStore.user));
      mostrarModalPerfil.value = false;
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Error al actualizar perfil');
  } finally {
    guardandoPerfil.value = false;
  }
};

const abrirCambiarPassword = () => {
  openUserMenu.value = false;
  formPassword.password_actual = '';
  formPassword.nuevo_password = '';
  formPassword.nuevo_password_confirmation = '';
  mostrarModalPassword.value = true;
};

const guardarPassword = async () => {
  if (formPassword.nuevo_password !== formPassword.nuevo_password_confirmation) {
    alert('La nueva contraseña y su confirmación no coinciden.');
    return;
  }
  guardandoPassword.value = true;
  try {
    const res = await axios.put('/api/auth/cambiar-password', formPassword);
    if (res.data.success) {
      alert('Contraseña actualizada exitosamente.');
      mostrarModalPassword.value = false;
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Error al cambiar contraseña');
  } finally {
    guardandoPassword.value = false;
  }
};
const mobileMenuOpen = ref(false);

const adminRef = ref(null);
const segRef = ref(null);

const isAdminActive = computed(() => {
  return ['urbanizaciones', 'inmuebles', 'personas'].includes(props.vistaActual);
});

const isSegActive = computed(() => {
  return ['usuarios', 'roles', 'vistas', 'auditoria'].includes(props.vistaActual);
});

const userIniciales = computed(() => {
  if (!authStore.user?.name) return 'U';
  return authStore.user.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
});

const irA = (vista) => {
  openAdmin.value = false;
  openSeg.value = false;
  mobileMenuOpen.value = false;
  emit('cambiarVista', vista);
};

const toggleAdmin = () => {
  openAdmin.value = !openAdmin.value;
  if (openAdmin.value) openSeg.value = false;
};

const toggleSeg = () => {
  openSeg.value = !openSeg.value;
  if (openSeg.value) openAdmin.value = false;
};

const seleccionarSubvista = (vista) => {
  openAdmin.value = false;
  openSeg.value = false;
  mobileMenuOpen.value = false;
  emit('cambiarVista', vista);
};

const handleClickOutside = (e) => {
  if (adminRef.value && !adminRef.value.contains(e.target)) {
    openAdmin.value = false;
  }
  if (segRef.value && !segRef.value.contains(e.target)) {
    openSeg.value = false;
  }
};

onMounted(() => {
  window.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  window.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.main-navbar {
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-subtle);
  position: sticky;
  top: 0;
  z-index: 40;
  box-shadow: var(--shadow-sm);
  width: 100%;
}

.nav-container {
  max-width: 100%;
  margin: 0 auto;
  padding: 0 24px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.nav-brand {
  display: flex;
  align-items: center;
}

.nav-tabs {
  display: flex;
  align-items: center;
  gap: 6px;
}

.nav-tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-secondary);
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
}

.nav-tab:hover {
  color: var(--text-primary);
  background: var(--bg-hover);
}

.nav-tab-active {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-primary);
  background: var(--bg-primary-subtle, rgba(114, 193, 165, 0.12));
  border: 1px solid var(--border-primary-subtle, rgba(114, 193, 165, 0.3));
}

.dropdown-wrap {
  position: relative;
}

.nav-tab-button {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-secondary);
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s;
}

.nav-tab-button:hover {
  color: var(--text-primary);
  background: var(--bg-hover);
}

.chevron-icon {
  transition: transform 0.2s ease;
}

.rotate-180 {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 250px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  box-shadow: var(--shadow-lg);
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  z-index: 50;
  backdrop-filter: blur(12px);
}

.drop-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid transparent;
  background: transparent;
  text-align: left;
  cursor: pointer;
  transition: all 0.15s ease;
  width: 100%;
}

.drop-item:hover {
  background: var(--bg-hover);
}

.drop-item.drop-active {
  background: var(--bg-primary-subtle, rgba(114, 193, 165, 0.12));
  border-color: var(--border-primary-subtle, rgba(114, 193, 165, 0.3));
}

.drop-icon {
  margin-top: 2px;
  color: var(--text-secondary);
}

.drop-item.drop-active .drop-icon {
  color: var(--color-primary);
}

.drop-text {
  display: flex;
  flex-direction: column;
}

.drop-title {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-primary);
}

.drop-desc {
  font-size: 10px;
  color: var(--text-muted);
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 14px;
}

.theme-toggle-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.btn-theme-toggle {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid var(--border-subtle);
  background: var(--bg-surface-elevated, var(--bg-surface));
  color: var(--text-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-theme-toggle:hover {
  background: var(--bg-hover);
  border-color: var(--color-primary);
  color: var(--color-primary);
  transform: translateY(-1px);
}

.theme-tooltip {
  position: absolute;
  top: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
  background: #1e293b;
  color: #ffffff;
  font-size: 11px;
  font-weight: 500;
  padding: 4px 8px;
  border-radius: 6px;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, transform 0.2s ease;
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
  z-index: 60;
}

.theme-toggle-wrap:hover .theme-tooltip {
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) translateY(2px);
}

.user-pill {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 6px 4px 10px;
  border-radius: 30px;
  background: var(--bg-hover);
  border: 1px solid var(--border-subtle);
}

.user-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--color-primary);
  color: #111827;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-info {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.user-name {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-primary);
}

.user-role {
  font-size: 10px;
  color: var(--text-muted);
}

.btn-logout {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: none;
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.btn-logout:hover {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
}

.mobile-only {
  display: none;
}

.desktop-only {
  display: flex;
}

.btn-burger {
  background: transparent;
  border: none;
  color: var(--text-primary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
}

.btn-icon {
  background: transparent;
  border: 1px solid var(--border-subtle);
  border-radius: 8px;
  padding: 6px;
  color: var(--text-secondary);
  cursor: pointer;
}

.mobile-drawer {
  position: fixed;
  top: 60px;
  left: 0;
  right: 0;
  bottom: 0;
  background: var(--bg-surface);
  z-index: 50;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  border-top: 1px solid var(--border-subtle);
}

.drawer-header {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border-subtle);
}

.user-pill-mobile {
  display: flex;
  align-items: center;
  gap: 12px;
}

.drawer-links {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}

.drawer-group-title {
  font-size: 10px;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  padding: 8px 12px 4px;
}

.drawer-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  color: var(--text-secondary);
  background: transparent;
  border: 1px solid transparent;
  text-align: left;
  cursor: pointer;
}

.drawer-link:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.drawer-link-active {
  background: var(--bg-primary-subtle, rgba(114, 193, 165, 0.12));
  color: var(--color-primary);
  border-color: var(--border-primary-subtle, rgba(114, 193, 165, 0.3));
  font-weight: 600;
}

.drawer-footer {
  padding: 16px 20px;
  border-top: 1px solid var(--border-subtle);
}

.btn-logout-mobile {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  border-radius: 10px;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.2);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

@media (max-width: 900px) {
  .desktop-only {
    display: none !important;
  }
  .mobile-only {
    display: flex !important;
  }
}

.dropdown-anim-enter-active,
.dropdown-anim-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.dropdown-anim-enter-from,
.dropdown-anim-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* ESTILOS MENÚ DROPDOWN DE USUARIO & MODALES */
.user-pill-clickable {
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}
.user-pill-clickable:hover {
  border-color: var(--color-primary);
  background: var(--bg-surface-elevated, var(--bg-hover));
}
.dropdown-user-menu {
  right: 0;
  left: auto;
  width: 240px;
}
.user-menu-header {
  padding: 8px 10px 10px;
  border-bottom: 1px solid var(--border-subtle);
  margin-bottom: 4px;
}
.drop-divider {
  height: 1px;
  background: var(--border-subtle);
  margin: 4px 0;
}
.drop-item-danger:hover {
  background: rgba(239, 68, 68, 0.1) !important;
}

/* MODALES */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 20px;
}
.modal-box {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  width: 100%;
  padding: 24px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
}
.modal-box-perfil {
  max-width: 480px;
}
.modal-icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--border-subtle);
}
.modal-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
}
.btn-close {
  background: transparent;
  border: none;
  font-size: 20px;
  color: var(--text-muted);
  cursor: pointer;
}
.input-block {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.input-block label {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--text-secondary);
}
.form-input {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 9px 12px;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  width: 100%;
}
.form-input:focus {
  border-color: var(--color-primary);
}
.grid-2-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 16px;
}
.btn-primary {
  background: var(--color-primary);
  color: #0b0f19;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-primary:hover {
  opacity: 0.9;
}
.btn-secondary {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 12px;
  cursor: pointer;
}

</style>
