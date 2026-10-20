<template>
  <div class="robledal-brand-container">
    <!-- Isotipo de Robledal (3 nodos conectados) -->
    <div class="robledal-isotipo" :style="{ width: size + 'px', height: size + 'px' }">
      <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
        <!-- Línea conectora inclinada -->
        <line x1="38" y1="46" x2="68" y2="76" stroke="#FFFFFF" stroke-width="7" stroke-linecap="round"/>
        <!-- Nodo superior izquierdo (blanco) -->
        <circle cx="35" cy="43" r="11" fill="#FFFFFF"/>
        <!-- Nodo central grande (verde menta tecnológico) -->
        <circle cx="78" cy="40" r="16" fill="var(--color-primary)"/>
        <!-- Nodo conector medio (blanco con aro) -->
        <circle cx="70" cy="78" r="8" fill="#FFFFFF"/>
        <!-- Nodo inferior derecho (verde menta) -->
        <circle cx="95" cy="98" r="7.5" fill="var(--color-primary)"/>
        <!-- Nodo inferior izquierdo (verde menta) -->
        <circle cx="48" cy="98" r="7.5" fill="var(--color-primary)"/>
      </svg>
    </div>

    <!-- Logotipo Tipográfico -->
    <div v-if="showText" class="robledal-text-block">
      <div class="robledal-title tracking-widest font-semibold" :style="{ color: isDark ? '#FFFFFF' : 'var(--color-primary)' }">
        R O B L E D A L
      </div>
      <div class="robledal-sub text-[10px] tracking-[0.25em] uppercase text-[var(--text-secondary)]">
        CONNECTING &bull; RESIDIA
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  size: { type: Number, default: 36 },
  showText: { type: Boolean, default: true },
  isDark: { type: Boolean, default: true }
});
</script>

<style scoped>
.robledal-brand-container {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  user-select: none;
}
.robledal-title {
  font-size: 15px;
  letter-spacing: 0.28em;
  line-height: 1.2;
}
.robledal-sub {
  font-size: 9px;
  letter-spacing: 0.22em;
  opacity: 0.85;
}
</style>
