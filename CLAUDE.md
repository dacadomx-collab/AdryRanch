# CLAUDE.md — Manual Operativo del Agente IA
## AdryRanch — Trail Nocturno La Paz, Edición Luna Llena
**Versión:** 1.1 | **Fecha:** 2026-06-22 | **Arquitecto:** Dacadomx

---

## 1. IDENTIDAD DEL PROYECTO

**Proyecto:** AdryRanch
**Cliente / Dueño:** AdryRanch — Evento "Trail Nocturno La Paz — Edición Luna Llena"
**Objetivo:** Landing page oficial de una carrera de trail nocturno en La Paz, Baja California Sur, México (sábado 24 de octubre de 2026). Presenta el evento, comunica la identidad mística/natural (cráneo de toro del desierto, atardecer chocando con la noche y la luna llena) y permite el registro de corredores con aportación de inscripción vía SPEI.
**Dominio de producción:** `https://[dominio-adryranch].com`
**Entorno local:** `C:\xampp\htdocs\AdryRanch\`
**Repositorio:** GitHub → rama `main` → auto-deploy vía GitHub Actions FTP

### Stack Tecnológico
- **Frontend:** HTML + CSS + JS nativo (landing estática de una sola página)
- **Backend:** PHP 8+ con `declare(strict_types=1)` obligatorio en todo archivo nuevo (`api/registro.php`)
- **Base de Datos:** MySQL/MariaDB vía PDO centralizado (`api/conexion.php`)
- **Servidor:** Apache/XAMPP local + hosting de producción (proveedor pendiente de definir)
- **IA (si aplica):** N/A — este proyecto no usa IA en producción

---

## 2. ESTRUCTURA DE CARPETAS

```
AdryRanch/
├── index.html                       ← Punto de entrada principal (landing del evento)
├── .htaccess                        ← Blindaje Apache Nivel Militar
├── .env                             ← Credenciales REALES (NUNCA en Git)
├── .env.example                     ← Plantilla pública (sí en Git)
├── .gitignore                       ← Protección del repositorio
├── CLAUDE.md                        ← Este archivo — manual del agente
│
├── api/                             ← Endpoints PHP (todos blindados)
│   ├── conexion.php                 ← Conexión PDO centralizada (leer desde .env)
│   ├── cors.php                     ← Gestor CORS centralizado
│   └── registro.php                 ← Endpoint de registro de corredores
│
├── css/
│   └── main.css
├── js/
│   └── main.js
├── assets/
│   └── img/                         ← Imágenes estáticas (cráneo de toro, luna, atardecer)
│
├── logs/                            ← Logs del sistema (bloqueados en .htaccess)
│   └── error.log
│
├── .github/
│   └── workflows/
│       └── deploy.yml               ← Pipeline CI/CD automático
│
└── knowledge/                       ← Memoria del sistema (bloqueada en .htaccess)
    ├── 00_ADN_Y_FILOSOFIA.md
    ├── 01_LEY_Y_PROTOCOLOS_DE_VUELO.md
    ├── 02_CODEX_Y_SCHEMA_MAESTRO.md
    ├── 03_CONTRATOS_API_Y_RUTAS.md
    ├── 04_ARQUITECTURA_Y_BLINDAJE.md
    ├── 05_MATRIZ_FINANCIERA_Y_VENTAS.md
    ├── 06_NUCLEO_COGNITIVO_Y_PROMPTS.md
    └── 07_UI_MODULOS_Y_PANTALLAS.md
```

---

## 3. LOS 18 MANDAMIENTOS — LEY SUPREMA

Referencia completa: `knowledge/01_LEY_Y_MANDAMIENTOS.md`

| # | Mandamiento | Resumen Ejecutivo |
| :--- | :--- | :--- |
| 1 | Mobile-First | Todo componente nace para celular. Sin anchos fijos (px) en contenedores. |
| 2 | Seguridad Nivel Militar | Sanitización + Prepared Statements. Blindaje SQLi, XSS, CSRF. |
| 3 | Modo Oscuro | Contraste mínimo WCAG 4.5:1. Tema fluido Light/Dark. |
| 4 | Anti-Alucinación | PROHIBIDO inventar variables. Si no está en el Codex, DETENERSE. |
| 5 | Contrato de API Estricto | No alterar propiedades JSON sin modificar el Contrato oficial. |
| 6 | Ejecución Determinística | Sin "mejoras" ni extensiones no solicitadas. |
| 7 | Naming Registry | `snake_case` backend/DB. `camelCase` frontend. |
| 8 | Dead Code | Auditoría de huérfanos antes de cada entrega. |
| 9 | Inmutabilidad del Sistema | No crear tablas ni alterar schema sin autorización explícita. |
| 10 | Sinónimos Prohibidos | Un solo nombre válido por concepto. Cero traducciones libres. |
| 11 | Arranque Blindado | Todo proyecto inicia con `.env`, `.htaccess` y conexión PDO. |
| 12 | **Bóveda de Secretos** | **PROHIBIDO hardcodear credenciales, tokens o API Keys. Todo en `.env`.** |
| 13 | Aislamiento de Entornos | Local NUNCA apunta a DB de producción. 3 entornos: Local/Staging/Prod. |
| 14 | CORS ≠ Auth | Todo endpoint POST/PUT/DELETE requiere autenticación real. Sin token = 401. |
| 15 | Agente Residente | Todo proyecto tiene `CLAUDE.md` actualizado. |
| 16 | CI/CD Inquebrantable | Deploy automático vía `deploy.yml`. Despliegue manual prohibido. |
| 17 | Documentación Viva | Módulo sin documentar = módulo no terminado. Hub de reportes obligatorio. |
| 18 | **Auditoría AXON DCD** | **Ningún proyecto a producción sin pasar el scanner perimetral AXON DCD.** |

---

## 4. REGLAS DE HIERRO — SEGURIDAD (INAMOVIBLES)

### 🚨 REGLA DE PROTECCIÓN LINGÜÍSTICA
- Este proyecto opera bajo el principio de Fricción Cero y terminología unificada.
- Tienes prohibido inventar nombres de variables, endpoints o interfaces que generen duplicidades o confusión técnica/comercial.
- Utiliza la tabla de mapeo del Codex de este proyecto (`knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md`) como la única verdad arquitectónica.

### PROHIBIDO absolutamente:
- Hardcodear contraseñas, API Keys, tokens, DSN de BD en cualquier archivo PHP o JS.
- Escribir credenciales en comentarios de código.
- Usar `require_once 'archivo.php'` sin `__DIR__` (rutas relativas simples).
- Usar `Access-Control-Allow-Origin: *` en endpoints que modifican datos.
- Modificar el `.htaccess` sin autorización explícita del Arquitecto.
- Crear nuevas tablas o alterar el schema de BD sin autorización explícita.
- Mostrar errores de PDO o PHP en el frontend (usar try/catch + logs).

### OBLIGATORIO siempre:
- Toda credencial: `getenv('NOMBRE_VARIABLE')` o `parse_ini_file()` desde el `.env`.
- Toda ruta PHP: `require_once __DIR__ . '/ruta/archivo.php'` — sin excepción.
- Toda conexión a BD: a través de `api/conexion.php` únicamente.
- Antes de generar código: verificar que variables existen en `02_DATABASE_SCHEMA_BLUEPRINT.md`.
- Al detectar credenciales hardcodeadas: reportar y corregir inmediatamente.

---

## 5. COMPORTAMIENTO DEL AGENTE (MODO DE OPERACIÓN)

**Modo:** Determinístico. No creativo. No expansivo.

### Antes de escribir código:
1. Consultar `03_CONTRATOS_API_Y_RUTAS.md` — respetar contratos de API existentes.
2. Verificar que las variables a usar están en `02_CODEX_Y_SCHEMA_MAESTRO.md`.
3. Confirmar que no se alteran tablas de BD (Mandamiento 9).
4. Ejecutar el PRE-CODE CHECKLIST de `01_LEY_Y_PROTOCOLOS_DE_VUELO.md`.

### Al terminar un módulo:
1. Actualizar `02_CODEX_Y_SCHEMA_MAESTRO.md` con nuevas tablas o variables.
2. Actualizar `03_CONTRATOS_API_Y_RUTAS.md` si se creó un nuevo endpoint.
3. Ejecutar el POST-CODE VALIDATION de `01_LEY_Y_PROTOCOLOS_DE_VUELO.md`.
4. Reportar al Arquitecto el estado del módulo.

### Regla de Cierre de Hito (3 condiciones simultáneas):
1. El código está escrito, guardado y funcional en el entorno local.
2. Todos los artefactos nuevos están registrados en el Codex.
3. Se ha emitido el Informe de Operación al Arquitecto.

---

## 6. PIPELINE CI/CD (GitHub Actions → FTP)

**Archivo:** `.github/workflows/deploy.yml`
**Trigger:** Push a rama `main`

**GitHub Secrets requeridos** (Settings → Secrets → Actions):
| Secret | Contenido |
| :--- | :--- |
| `FTP_SERVER` | Servidor FTP del hosting |
| `FTP_USERNAME` | Usuario FTP |
| `FTP_PASSWORD` | Contraseña FTP (NUNCA en código) |
| `FTP_REMOTE_DIR` | Ruta remota (ej. `/public_html/`) |

**Excluido del deploy:**
- Credenciales: `.env`
- Documentación interna: `knowledge/`
- Herramientas dev: `.claude/`, backups
- Logs: `logs/`

---

## 7. ARCHIVOS QUE NUNCA SE MODIFICAN SIN AUTORIZACIÓN

- `knowledge/01_LEY_Y_PROTOCOLOS_DE_VUELO.md` — Los Mandamientos son ley.
- `.htaccess` — Blindaje crítico de seguridad.
- `.env` — Credenciales de producción.
- Schema de BD — Inmutabilidad del sistema.

## 8. ARCHIVOS QUE NUNCA SE SUBEN A GIT

- `.env` (cualquier variante real)
- `info.txt`
- `logs/` (directorio completo)
- `backups/` (directorio completo)
- Cualquier archivo con credenciales reales.

---

## 9.5. EXCEPCIÓN DE ARQUITECTURA — CONEXIÓN REMOTA FORZADA EN LOCAL

> ⚠️ **Directiva inamovible del Arquitecto (2026-06-22).** Este proyecto **NO sigue** la interpretación estándar del Mandamiento 13 (Aislamiento de Entornos). Queda registrado como excepción explícita y permanente:

- El entorno local (`C:\xampp\htdocs\AdryRanch\`, servido por Apache/XAMPP) **se conecta directamente a la base de datos remota de producción**: `DB_HOST="chir205.websitehostserver.net"`, `DB_NAME="tourfindycom_AdryRanch_DB"`.
- **NO existe ni se creará** una base de datos MySQL local para este proyecto. No migrar `DB_HOST` a `localhost` bajo ninguna circunstancia, aunque parezca la práctica estándar.
- **Motivo:** el hosting compartido de este proyecto no ofrece una instancia de MySQL local equivalente; las pruebas "en vivo" durante el desarrollo se hacen directamente contra la BD real de `tourfindycom_AdryRanch_DB`.
- **Implicación de seguridad:** toda prueba local desde `index.html`/`api/registro.php` escribe registros reales en la tabla `registro_corredores` de producción. Cualquier dato de prueba insertado durante desarrollo debe limpiarse manualmente desde phpMyAdmin/consola antes de lanzar la inscripción real al público.
- Las credenciales viven únicamente en `.env` (Mandamiento 12), nunca hardcodeadas, y `.env` permanece fuera de Git (Mandamiento 8 de este documento).

---

## 10. HISTORIAL DE VERSIONES

| Versión | Fecha | Cambio Principal |
| :--- | :--- | :--- |
| v1.0 | 2026-06-11 | Creación inicial del manual operativo (plantilla genérica DCD LABS / VECTOR_CERO) |
| v1.1 | 2026-06-22 | Limpieza de identidad: branding y referencias adaptados a AdryRanch — Trail Nocturno La Paz. Tabla de los 18 Mandamientos conservada sin alteraciones. |
| v1.2 | 2026-06-22 | Registrada Excepción de Arquitectura: conexión remota forzada a `tourfindycom_AdryRanch_DB` desde entorno local, anulando la interpretación estándar del Mandamiento 13 para este proyecto. |
| v1.3 | 2026-06-23 | Overhaul visual BCS calibrado desde `logo.jpg` (terracota `#d96b27` / rojo ocaso `#b23b22`): recoloración del Dashboard, hover de autoridad en el logo del navbar, Conmutador de Iluminación (Day/Night, persistente en `localStorage`), botón Volver Arriba, enlace "Acceso Admin" en navbar/menú móvil, y galería ARF-Grid de fotos reales en "Ediciones Anteriores". |
| v1.4 | 2026-06-23 | Corregido bug real en `.htaccess` (regex `^/api/` no coincidía con la URI local `/AdryRanch/api/...`, causaba 403 en todos los endpoints). Topbar del Dashboard: logo institucional `login.png` reemplaza el ícono lineal dorado. Nuevo Conmutador de Iluminación interno del Dashboard (modo claro "broken white", independiente del toggle público). |
