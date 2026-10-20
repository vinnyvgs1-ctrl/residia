# Especificación de Identidad Visual: Robledal Connecting & Theming Engine para Residia

## 1. Análisis Cromático del Logo Oficial (Robledal Connecting)

A partir de la identidad corporativa de **ROBLEDAL CONNECTING**, extraemos la paleta cromática exacta y de alta fidelidad:

| Elemento | Código HEX | Tonalidad / Semántica | Uso en la Interfaz |
| :--- | :--- | :--- | :--- |
| **Verde Menta / Aqua Robledal** | `#72C1A5` | Menta tecnológica fresca / Isotipo principal | Color primario, botones de acción principal, estado activo, badges de éxito. |
| **Gris Antracita Oscuro (Dark Bg)** | `#323637` | Carbón mate premium / Fondo del logo oscuro | Fondo principal en Modo Oscuro (`--bg-primary`), barras de navegación. |
| **Gris Antracita Elevado (Card/Surface)** | `#3F4445` | Carbón elevado | Superficie de tarjetas, modales y tablas en modo oscuro. |
| **Blanco Puro** | `#FFFFFF` | Nodos conectores y tipografía | Texto de alto contraste, nodos de conexión y fondos en modo claro. |
| **Gris Suave Minimal (Light Bg)** | `#F8FAFB` | Pizarra blanco cálido | Fondo en Modo Claro (`--bg-primary-light`). |
| **Gris Bordes Sutiles** | `#4B5153` (Dark) / `#E2E8F0` (Light) | Separadores | Bordes sutiles para inputs, tablas y divisiones de garita. |
| **Texto Secundario / Subtítulos** | `#9BA3A7` | Gris neutro equilibrado | Etiquetas, timestamps de garita, metadatos. |

---

## 2. Definición de los 2 Temas Oficiales en `sec_temas`

Ambos temas se registrarán en la tabla `sec_temas` en formato JSONB para alimentar el motor dinámico de CSS Variables:

### A. Tema 1: `robledal-dark` (Modo Oscuro Oficial - Logo Base)
Fiel al contenedor oscuro del logo de Robledal:
```json
{
  "--bg-primary": "#323637",
  "--bg-surface": "#2B2E2F",
  "--bg-card": "#3F4445",
  "--bg-hover": "#4B5153",
  "--text-primary": "#FFFFFF",
  "--text-secondary": "#9BA3A7",
  "--text-muted": "#6E777B",
  "--color-primary": "#72C1A5",
  "--color-primary-hover": "#5EB093",
  "--color-primary-subtle": "rgba(114, 193, 165, 0.15)",
  "--color-accent": "#FFFFFF",
  "--color-warning": "#F59E0B",
  "--color-danger": "#EF4444",
  "--color-success": "#72C1A5",
  "--border-subtle": "rgba(255, 255, 255, 0.10)",
  "--border-focus": "#72C1A5",
  "--shadow-elevation": "0 10px 25px -5px rgba(0, 0, 0, 0.5)"
}
```

### B. Tema 2: `robledal-light` (Modo Claro Minimalista)
Limpio, espacioso y corporativo con acento en el verde menta y textos antracita del logo:
```json
{
  "--bg-primary": "#F8FAFB",
  "--bg-surface": "#FFFFFF",
  "--bg-card": "#FFFFFF",
  "--bg-hover": "#F1F5F7",
  "--text-primary": "#1E2223",
  "--text-secondary": "#636C70",
  "--text-muted": "#9BA3A7",
  "--color-primary": "#72C1A5",
  "--color-primary-hover": "#5EB093",
  "--color-primary-subtle": "rgba(114, 193, 165, 0.12)",
  "--color-accent": "#323637",
  "--color-warning": "#D97706",
  "--color-danger": "#DC2626",
  "--color-success": "#059669",
  "--border-subtle": "#E4E8EC",
  "--border-focus": "#72C1A5",
  "--shadow-elevation": "0 4px 20px -2px rgba(50, 54, 55, 0.08)"
}
```

---

## 3. Tipografía e Isotipo Conector

- **Fuente Recomendada**: `'Plus Jakarta Sans'` o `'Outfit'` (geométrica, moderna, espaciada y limpia, idéntica al tracking ancho de `R O B L E D A L`).
- **Detalle de Isotipo**: Los tres nodos conectados representan comunicación, garita y residencia (perfecto para loaders animados y badges de estado en tiempo real).
