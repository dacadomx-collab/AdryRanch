/* =====================================================================
   AdryRanch — Centro de Control (Dashboard administrativo)
   dashboard.js
   ===================================================================== */

const FECHA_EVENTO = new Date("2026-10-24T00:00:00");

let corredoresCache = [];
let sesionActual = { nombre: "", rol: "" };

document.addEventListener("DOMContentLoaded", function () {
  inicializarLogin();
  inicializarNavegacionModulos();
  inicializarLogout();
  inicializarFormularioTarea();
  inicializarFormularioUsuario();
  verificarSesionExistente();
});

/* ── Utilidades ─────────────────────────────────────────────────────── */
function mostrarMensaje(elemento, texto, tipo) {
  if (!elemento) return;
  elemento.textContent = texto;
  elemento.classList.remove("error", "cargando");
  elemento.classList.add("visible", tipo);
}

function ocultarMensaje(elemento) {
  if (!elemento) return;
  elemento.classList.remove("visible", "error", "cargando");
  elemento.textContent = "";
}

async function llamarApi(ruta, opciones) {
  const respuesta = await fetch("../api/" + ruta, {
    credentials: "same-origin",
    headers: { "Content-Type": "application/json" },
    ...opciones,
  });
  const datos = await respuesta.json();
  if (!respuesta.ok) {
    throw new Error(datos.mensaje || "Ocurrió un problema inesperado.");
  }
  return datos;
}

/* ── Sesión y Login ─────────────────────────────────────────────────── */
function mostrarDashboard() {
  document.getElementById("pantallaLogin").style.display = "none";
  document.getElementById("pantallaDashboard").classList.add("activa");
  document.getElementById("nombreUsuarioActivo").textContent = sesionActual.nombre;
  document.getElementById("rolUsuarioActivo").textContent = sesionActual.rol;
  document.getElementById("botonModuloUsuarios").hidden = sesionActual.rol !== "super_admin";

  cargarMetricasYCorredores();
}

async function verificarSesionExistente() {
  try {
    const datos = await llamarApi("sesion_admin.php");
    sesionActual = { nombre: datos.nombre, rol: datos.rol };
    mostrarDashboard();
  } catch (error) {
    // Sin sesión activa: se queda en la pantalla de login.
  }
}

function inicializarLogin() {
  const formulario = document.getElementById("formularioLogin");
  const mensaje = document.getElementById("mensajeLogin");
  if (!formulario) return;

  formulario.addEventListener("submit", async function (evento) {
    evento.preventDefault();
    ocultarMensaje(mensaje);
    mostrarMensaje(mensaje, "Verificando acceso...", "cargando");

    const email = document.getElementById("emailLogin").value;
    const password = document.getElementById("passwordLogin").value;

    try {
      const datos = await llamarApi("login_admin.php", {
        method: "POST",
        body: JSON.stringify({ email, password }),
      });
      sesionActual = { nombre: datos.nombre, rol: datos.rol };
      ocultarMensaje(mensaje);
      mostrarDashboard();
    } catch (error) {
      mostrarMensaje(mensaje, error.message, "error");
    }
  });
}

function inicializarLogout() {
  const boton = document.getElementById("botonLogout");
  if (!boton) return;

  boton.addEventListener("click", async function () {
    await llamarApi("logout_admin.php", { method: "POST" });
    document.getElementById("pantallaDashboard").classList.remove("activa");
    document.getElementById("pantallaLogin").style.display = "flex";
    document.getElementById("formularioLogin").reset();
  });
}

/* ── Navegación entre módulos ──────────────────────────────────────── */
function inicializarNavegacionModulos() {
  const botones = document.querySelectorAll(".boton-modulo");
  botones.forEach(function (boton) {
    boton.addEventListener("click", function () {
      botones.forEach((b) => b.classList.remove("activo"));
      boton.classList.add("activo");

      document.querySelectorAll(".modulo-panel").forEach((panel) => panel.classList.add("oculto"));
      const modulo = boton.dataset.modulo;
      document.getElementById("modulo" + capitalizar(modulo)).classList.remove("oculto");

      if (modulo === "corredores") cargarMetricasYCorredores();
      if (modulo === "tareas") cargarTareas();
      if (modulo === "usuarios") cargarUsuarios();
    });
  });
}

function capitalizar(texto) {
  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

/* ── Módulo D: Métricas y Línea de Tiempo ──────────────────────────── */
function actualizarCuentaRegresiva() {
  const hoy = new Date();
  const milisegundosRestantes = FECHA_EVENTO.getTime() - hoy.getTime();
  const diasRestantes = Math.max(0, Math.ceil(milisegundosRestantes / (1000 * 60 * 60 * 24)));
  document.getElementById("diasRestantes").textContent = diasRestantes;
}

function renderizarMetricas() {
  const total = corredoresCache.length;
  const confirmados = corredoresCache.filter((c) => c.estatusPago === "confirmado").length;
  const enRevision = total - confirmados;
  const seQuedanAlAfter = corredoresCache.filter((c) => c.seQuedaAlAfter === "si").length;

  document.getElementById("metricaTotal").textContent = total;
  document.getElementById("metricaConfirmados").textContent = confirmados;
  document.getElementById("metricaEnRevision").textContent = enRevision;
  document.getElementById("metricaAfter").textContent = seQuedanAlAfter;
}

/* ── Módulo A: Visor de Corredores ─────────────────────────────────── */
let secuenciaCargaCorredores = 0;

async function cargarMetricasYCorredores() {
  actualizarCuentaRegresiva();
  const mensaje = document.getElementById("mensajeCorredores");
  const idSecuencia = ++secuenciaCargaCorredores;
  try {
    const datos = await llamarApi("dashboard_corredores.php");
    if (idSecuencia !== secuenciaCargaCorredores) return;
    corredoresCache = datos.corredores;
    renderizarMetricas();
    renderizarTablaCorredores();
  } catch (error) {
    if (idSecuencia !== secuenciaCargaCorredores) return;
    mostrarMensaje(mensaje, error.message, "error");
  }
}

function renderizarTablaCorredores() {
  const cuerpo = document.getElementById("cuerpoTablaCorredores");
  cuerpo.innerHTML = "";

  corredoresCache.forEach(function (corredor) {
    const fila = document.createElement("tr");
    const confirmado = corredor.estatusPago === "confirmado";

    fila.innerHTML = `
      <td>${escaparHtml(corredor.nombreCompleto)}</td>
      <td>${escaparHtml(corredor.telefono)}</td>
      <td>${escaparHtml(corredor.correo)}</td>
      <td>${escaparHtml(corredor.paquete)}</td>
      <td>${corredor.seQuedaAlAfter === "si" ? "Sí" : "No"}</td>
      <td>${escaparHtml(corredor.referenciaPago || "—")}</td>
      <td><span class="insignia-estatus ${confirmado ? "confirmado" : "en-revision"}">
        ${confirmado ? "Asistencia Asegurada" : "Aportación en Revisión"}
      </span></td>
      <td>${confirmado ? "" : `<button class="boton-confirmar-fila" data-id="${corredor.idRegistro}">Confirmar</button>`}</td>
    `;
    cuerpo.appendChild(fila);
  });

  cuerpo.querySelectorAll(".boton-confirmar-fila").forEach(function (boton) {
    boton.addEventListener("click", async function () {
      boton.disabled = true;
      try {
        await llamarApi("dashboard_corredores.php", {
          method: "POST",
          body: JSON.stringify({ accion: "confirmar", idRegistro: Number(boton.dataset.id) }),
        });
        await cargarMetricasYCorredores();
      } catch (error) {
        mostrarMensaje(document.getElementById("mensajeCorredores"), error.message, "error");
        boton.disabled = false;
      }
    });
  });
}

function escaparHtml(texto) {
  const div = document.createElement("div");
  div.textContent = texto ?? "";
  return div.innerHTML;
}

/* ── Módulo B: Task Synchronizer ───────────────────────────────────── */
async function cargarResponsables() {
  const select = document.getElementById("asignadoATarea");
  try {
    const datos = await llamarApi("dashboard_tareas.php?responsables=1");
    datos.responsables.forEach(function (responsable) {
      const opcion = document.createElement("option");
      opcion.value = responsable.idUsuario;
      opcion.textContent = responsable.nombre;
      select.appendChild(opcion);
    });
  } catch (error) {
    // Silencioso: el select se queda solo con "Sin asignar".
  }
}

let secuenciaCargaTareas = 0;

async function cargarTareas() {
  const mensaje = document.getElementById("mensajeTareas");
  const idSecuencia = ++secuenciaCargaTareas;
  try {
    if (document.getElementById("asignadoATarea").childElementCount <= 1) {
      await cargarResponsables();
    }
    const datos = await llamarApi("dashboard_tareas.php");
    if (idSecuencia !== secuenciaCargaTareas) return; // una carga más reciente ya está en curso
    renderizarTareas(datos.tareas);
  } catch (error) {
    if (idSecuencia !== secuenciaCargaTareas) return;
    mostrarMensaje(mensaje, error.message, "error");
  }
}

function renderizarTareas(tareas) {
  const columnas = {
    pendiente: document.getElementById("listaTareasPendiente"),
    en_progreso: document.getElementById("listaTareasEnProgreso"),
    completado: document.getElementById("listaTareasCompletado"),
  };
  Object.values(columnas).forEach((columna) => (columna.innerHTML = ""));

  tareas.forEach(function (tarea) {
    const tarjeta = document.createElement("div");
    tarjeta.className = "tarjeta-tarea";
    tarjeta.innerHTML = `
      <h4>${escaparHtml(tarea.titulo)}</h4>
      ${tarea.descripcion ? `<p>${escaparHtml(tarea.descripcion)}</p>` : ""}
      <p class="meta-tarea">Responsable: ${escaparHtml(tarea.asignadoNombre || "Sin asignar")}</p>
      <select data-id="${tarea.idTarea}">
        <option value="pendiente" ${tarea.estatus === "pendiente" ? "selected" : ""}>Pendiente</option>
        <option value="en_progreso" ${tarea.estatus === "en_progreso" ? "selected" : ""}>En Progreso</option>
        <option value="completado" ${tarea.estatus === "completado" ? "selected" : ""}>Completado</option>
      </select>
    `;

    tarjeta.querySelector("select").addEventListener("change", async function (evento) {
      try {
        await llamarApi("dashboard_tareas.php", {
          method: "POST",
          body: JSON.stringify({
            accion: "actualizar_estatus",
            idTarea: tarea.idTarea,
            estatus: evento.target.value,
          }),
        });
        await cargarTareas();
      } catch (error) {
        mostrarMensaje(document.getElementById("mensajeTareas"), error.message, "error");
      }
    });

    columnas[tarea.estatus].appendChild(tarjeta);
  });
}

function inicializarFormularioTarea() {
  const formulario = document.getElementById("formularioNuevaTarea");
  if (!formulario) return;

  formulario.addEventListener("submit", async function (evento) {
    evento.preventDefault();
    const mensaje = document.getElementById("mensajeTareas");

    try {
      await llamarApi("dashboard_tareas.php", {
        method: "POST",
        body: JSON.stringify({
          accion: "crear",
          titulo: document.getElementById("tituloTarea").value,
          descripcion: document.getElementById("descripcionTarea").value,
          asignadoA: document.getElementById("asignadoATarea").value,
          fechaLimite: document.getElementById("fechaLimiteTarea").value,
        }),
      });
      formulario.reset();
      ocultarMensaje(mensaje);
      await cargarTareas();
    } catch (error) {
      mostrarMensaje(mensaje, error.message, "error");
    }
  });
}

/* ── Módulo C: User Creator Panel ──────────────────────────────────── */
async function cargarUsuarios() {
  const mensaje = document.getElementById("mensajeUsuarios");
  try {
    const datos = await llamarApi("dashboard_usuarios.php");
    renderizarUsuarios(datos.usuarios);
  } catch (error) {
    mostrarMensaje(mensaje, error.message, "error");
  }
}

function renderizarUsuarios(usuarios) {
  const cuerpo = document.getElementById("cuerpoTablaUsuarios");
  cuerpo.innerHTML = "";

  usuarios.forEach(function (usuario) {
    const fila = document.createElement("tr");
    fila.innerHTML = `
      <td>${escaparHtml(usuario.nombre)}</td>
      <td>${escaparHtml(usuario.email)}</td>
      <td>${escaparHtml(usuario.rol)}</td>
      <td>${escaparHtml(usuario.creadoAt)}</td>
    `;
    cuerpo.appendChild(fila);
  });
}

function inicializarFormularioUsuario() {
  const formulario = document.getElementById("formularioNuevoUsuario");
  if (!formulario) return;

  formulario.addEventListener("submit", async function (evento) {
    evento.preventDefault();
    const mensaje = document.getElementById("mensajeUsuarios");

    try {
      await llamarApi("dashboard_usuarios.php", {
        method: "POST",
        body: JSON.stringify({
          nombre: document.getElementById("nombreNuevoUsuario").value,
          email: document.getElementById("emailNuevoUsuario").value,
          password: document.getElementById("passwordNuevoUsuario").value,
          rol: document.getElementById("rolNuevoUsuario").value,
        }),
      });
      formulario.reset();
      ocultarMensaje(mensaje);
      await cargarUsuarios();
    } catch (error) {
      mostrarMensaje(mensaje, error.message, "error");
    }
  });
}
