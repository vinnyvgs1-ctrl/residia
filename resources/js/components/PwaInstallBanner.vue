<template>
  <transition name="slide-up">
    <div v-if="mostrarBanner" class="pwa-banner">
      <div class="pwa-banner-content">
        <div class="pwa-icon-wrap">
          <Smartphone :size="20" class="text-cyan-400" />
        </div>
        <div class="pwa-text">
          <span class="pwa-title">Instalar Residia en tu Teléfono</span>
          <span class="pwa-sub">Acceso rápido a Garita, pases QR instantáneos y notificaciones de llegada.</span>
        </div>
      </div>

      <div class="pwa-actions">
        <button @click="solicitarPermisoNotificaciones" class="btn-push-sub" :title="notifActivas ? 'Notificaciones activas' : 'Activar Notificaciones'">
          <Bell :size="15" :class="{ 'text-amber-400': notifActivas }" />
          <span>{{ notifActivas ? 'Alertas Activas' : 'Activar Alertas' }}</span>
        </button>

        <button v-if="puedeInstalar" @click="instalarApp" class="btn-install">
          <Download :size="15" />
          <span>Instalar App</span>
        </button>

        <button @click="cerrarBanner" class="btn-close" title="Descartar">&times;</button>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Smartphone, Bell, Download } from 'lucide-vue-next';

const mostrarBanner = ref(false);
const puedeInstalar = ref(false);
const notifActivas = ref(false);
let deferredPrompt = null;

onMounted(() => {
  // 1. Detección de Prompt de instalación PWA
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    puedeInstalar.value = true;
    mostrarBanner.value = true;
  });

  // 2. Comprobar si ya soporta y tiene notificaciones
  if ('Notification' in window) {
    notifActivas.value = Notification.permission === 'granted';
    // Si aún no ha decidido y no está en standalone, sugerir banner
    if (Notification.permission === 'default' && !window.matchMedia('(display-mode: standalone)').matches) {
      setTimeout(() => {
        mostrarBanner.value = true;
      }, 3000);
    }
  }
});

const instalarApp = async () => {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  const { outcome } = await deferredPrompt.userChoice;
  if (outcome === 'accepted') {
    puedeInstalar.value = false;
    mostrarBanner.value = false;
  }
  deferredPrompt = null;
};

const solicitarPermisoNotificaciones = async () => {
  if (!('Notification' in window)) {
    alert('Tu navegador no soporta notificaciones de escritorio o push.');
    return;
  }

  try {
    const permission = await Notification.requestPermission();
    if (permission === 'granted') {
      notifActivas.value = true;

      // Obtener suscripción Service Worker si está disponible
      if ('serviceWorker' in navigator) {
        const reg = await navigator.serviceWorker.ready;
        // Mock de endpoint para suscripción interna
        const subData = {
          endpoint: 'https://fcm.googleapis.com/fcm/send/residia_token_' + Math.random().toString(36).substring(2),
          p256dh: 'mock_key_p256dh',
          auth: 'mock_auth_secret'
        };

        await axios.post('/api/push/suscribir', subData);
      }

      // Disparar notificación local de confirmación
      new Notification('¡Residia Alertas Activadas!', {
        body: 'Recibirás avisos instantáneos de visitas en garita y paquetes.',
        icon: '/icons/icon-192.png'
      });
    }
  } catch (error) {
    console.error('Error al solicitar permiso de notificaciones:', error);
  }
};

const cerrarBanner = () => {
  mostrarBanner.value = false;
};
</script>

<style scoped>
.pwa-banner {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  left: 1.5rem;
  max-width: 650px;
  margin: 0 auto;
  background: rgba(15, 23, 42, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(6, 182, 212, 0.3);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 0 15px rgba(6, 182, 212, 0.15);
  border-radius: 0.75rem;
  padding: 0.875rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  z-index: 999;
}

.pwa-banner-content {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.pwa-icon-wrap {
  width: 40px;
  height: 40px;
  border-radius: 0.5rem;
  background: rgba(6, 182, 212, 0.12);
  border: 1px solid rgba(6, 182, 212, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.pwa-text {
  display: flex;
  flex-direction: column;
}

.pwa-title {
  font-weight: 700;
  color: #f8fafc;
  font-size: 0.9rem;
}

.pwa-sub {
  font-size: 0.75rem;
  color: #94a3b8;
}

.pwa-actions {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-shrink: 0;
}

.btn-push-sub {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #e2e8f0;
  padding: 0.45rem 0.75rem;
  border-radius: 0.4rem;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-push-sub:hover {
  background: rgba(255, 255, 255, 0.12);
}

.btn-install {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: #0284c7;
  color: #fff;
  border: none;
  padding: 0.45rem 0.85rem;
  border-radius: 0.4rem;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-install:hover {
  background: #0369a1;
}

.btn-close {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 1.25rem;
  cursor: pointer;
  padding: 0 0.25rem;
  line-height: 1;
}

.btn-close:hover {
  color: #fff;
}

/* Animations */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

@media (max-width: 640px) {
  .pwa-banner {
    flex-direction: column;
    align-items: flex-start;
  }
  .pwa-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
