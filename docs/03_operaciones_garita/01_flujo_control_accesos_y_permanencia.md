# Operaciones de Garita: Flujos de Control de Accesos y Permanencia

## 1. Ingreso en Garita
1. **Identificación**: Digitación de cédula/pasaporte.
   - Si no existe: Alta rápida (Nombres + Apellidos).
   - Si existe: Autocompletado de datos en pantalla.
2. **Validación de Veto**: Verificación automática en `op_lista_negra`.
3. **Tipo de Ingreso**:
   - **Peatonal**: Motivo de visita.
   - **Vehicular**: Placa, marca, color, cantidad de ocupantes y acompañantes (JSONB).
4. **Destino**: Manzana/Bloque + Villa/Departamento.
5. **Estado Inicial**: `DENTRO`.

---

## 2. Control de Salida
1. Búsqueda por placa o documento.
2. Verificación de tiempo de permanencia con cálculo de duración.
3. Alerta visual (semáforo amarillo) si superó el tiempo máximo configurado en la urbanización.
4. Registro de salida: `acc_fecha_salida`, `acc_guardia_salida_usr_id`, `acc_garita_salida_id`, estado `SALIO`.
