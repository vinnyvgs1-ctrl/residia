# Seguridad Perimetral: API Shielding con HMAC-SHA256 y Redis

## 1. Cabeceras Obligatorias
Toda petición de entrada a las APIs de Residia debe incluir:
- `X-App-Key`: Identificador público de cliente (Web, Garita App, Mobile).
- `X-App-Timestamp`: Marca de tiempo Unix en segundos.
- `X-App-Signature`: Hash criptográfico HMAC-SHA256.
- `X-Nonce`: Cadena aleatoria de 32 caracteres para mitigación de repetición.

---

## 2. Validación de Nonces en Redis
```php
$nonceKey = "shield:nonce:{$nonce}";
$isUnique = Redis::set($nonceKey, '1', 'EX', 300, 'NX');
if (!$isUnique) {
    return response()->json(['error' => 'REPLAY_ATTACK_DETECTED'], 403);
}
```

## 3. Ventana Temporal
- Se rechaza cualquier petición con diferencia superior a 300 segundos ($\pm 5\text{ min}$).
