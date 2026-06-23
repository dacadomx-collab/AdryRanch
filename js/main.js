/* =====================================================================
   AdryRanch — Trail Nocturno La Paz · Edición Luna Llena
   main.js — Interacciones de la Landing Page
   ===================================================================== */

document.addEventListener("DOMContentLoaded", function () {
  inicializarNavbar();
  inicializarHamburguesa();
  inicializarScrollSuave();
  inicializarFormularioRegistro();
  inicializarModoIluminacion();
  inicializarBotonVolverArriba();
});

/* ── Navbar: cambia de transparente a sólida al hacer scroll ──────── */
function inicializarNavbar() {
  const navbar = document.querySelector(".navbar-arf");
  if (!navbar) return;

  window.addEventListener("scroll", function () {
    navbar.classList.toggle("navbar-scroll", window.scrollY > 40);
  });
}

/* ── Menú hamburguesa ───────────────────────────────────────────────── */
function inicializarHamburguesa() {
  const botonHamburguesa = document.querySelector(".boton-hamburguesa");
  const navbarLinks = document.querySelector(".navbar-links");
  if (!botonHamburguesa || !navbarLinks) return;

  botonHamburguesa.addEventListener("click", function () {
    botonHamburguesa.classList.toggle("activo");
    navbarLinks.classList.toggle("abierto");
  });

  navbarLinks.querySelectorAll("a").forEach(function (enlace) {
    enlace.addEventListener("click", function () {
      botonHamburguesa.classList.remove("activo");
      navbarLinks.classList.remove("abierto");
    });
  });
}

/* ── Scroll suave para anclas internas ─────────────────────────────── */
function inicializarScrollSuave() {
  document.querySelectorAll('a[href^="#"]').forEach(function (enlace) {
    enlace.addEventListener("click", function (evento) {
      const destino = document.querySelector(enlace.getAttribute("href"));
      if (!destino) return;
      evento.preventDefault();
      destino.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  });
}

/* ── Flujo de Registro → Pantalla de Aportación SPEI ───────────────────
   Envía el formulario a api/registro.php (tabla `registro_corredores`,
   confirmada por el Arquitecto). Ver knowledge/03_CONTRATOS_API_Y_RUTAS.md §0.
   ───────────────────────────────────────────────────────────────────── */
function inicializarFormularioRegistro() {
  const formulario = document.getElementById("formularioRegistro");
  const panelPago = document.getElementById("panelPago");
  const mensaje = document.getElementById("mensajeFormulario");
  const botonEnviar = formulario ? formulario.querySelector("button[type='submit']") : null;
  if (!formulario || !panelPago || !mensaje || !botonEnviar) return;

  const textoOriginalBoton = botonEnviar.innerHTML;

  function mostrarMensaje(texto, tipo) {
    mensaje.textContent = texto;
    mensaje.classList.remove("error", "cargando");
    mensaje.classList.add("visible", tipo);
  }

  function ocultarMensaje() {
    mensaje.classList.remove("visible", "error", "cargando");
    mensaje.textContent = "";
  }

  formulario.addEventListener("submit", async function (evento) {
    evento.preventDefault();
    ocultarMensaje();
    mostrarMensaje("Reservando tu lugar bajo la luna...", "cargando");
    botonEnviar.disabled = true;
    botonEnviar.innerHTML = "Procesando...";

    const datosFormulario = Object.fromEntries(new FormData(formulario).entries());

    try {
      const respuesta = await fetch("api/registro.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(datosFormulario),
      });

      const datos = await respuesta.json();

      if (!respuesta.ok) {
        throw new Error(datos.mensaje || "No pudimos procesar tu registro.");
      }

      document.getElementById("referenciaPagoTexto").textContent = datos.referenciaPago;

      ocultarMensaje();
      formulario.style.display = "none";
      panelPago.classList.add("visible");
      panelPago.scrollIntoView({ behavior: "smooth", block: "start" });
    } catch (error) {
      mostrarMensaje(error.message || "Ocurrió un problema al unirte a la palomilla. Intenta de nuevo.", "error");
      botonEnviar.disabled = false;
      botonEnviar.innerHTML = textoOriginalBoton;
    }
  });
}

/* ── Conmutador de Iluminación (Day/Night BCS) ──────────────────────────
   Alterna entre el modo místico nocturno (azul) y el modo ocaso/atardecer
   BCS (terracota/rojo ocaso), persistiendo la preferencia en localStorage.
   ───────────────────────────────────────────────────────────────────── */
const CLAVE_MODO_ILUMINACION = "adryranch_modo_iluminacion";

function inicializarModoIluminacion() {
  const boton = document.getElementById("botonModoIluminacion");
  if (!boton) return;

  const iconoSol = boton.querySelector(".icono-modo-sol");
  const iconoLuna = boton.querySelector(".icono-modo-luna");

  function aplicarModo(modo) {
    document.body.classList.toggle("modo-atardecer", modo === "atardecer");
    iconoSol.classList.toggle("oculto", modo === "atardecer");
    iconoLuna.classList.toggle("oculto", modo !== "atardecer");
  }

  const modoGuardado = localStorage.getItem(CLAVE_MODO_ILUMINACION) || "noche";
  aplicarModo(modoGuardado);

  boton.addEventListener("click", function () {
    const modoActual = document.body.classList.contains("modo-atardecer") ? "atardecer" : "noche";
    const modoNuevo = modoActual === "atardecer" ? "noche" : "atardecer";
    localStorage.setItem(CLAVE_MODO_ILUMINACION, modoNuevo);
    aplicarModo(modoNuevo);
  });
}

/* ── Botón Volver Arriba (Scroll-to-Top) ───────────────────────────────── */
function inicializarBotonVolverArriba() {
  const boton = document.getElementById("botonVolverArriba");
  if (!boton) return;

  window.addEventListener("scroll", function () {
    boton.classList.toggle("visible", window.scrollY > 400);
  });

  boton.addEventListener("click", function () {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
}
