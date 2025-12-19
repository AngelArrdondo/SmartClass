// main.js - lógica de urgencia, scrollspy, validación y login
document.addEventListener('DOMContentLoaded', () => {

  const URG_KEY = 'smartclass_urgency';
  const urgencyBar = document.getElementById('urgencyBar');
  const urgencyText = urgencyBar.querySelector('.urgency-text');
  const urgencyChoose = document.getElementById('urgencyChoose');

  // Inicializa el estado de la barra de urgencia: plazas disponibles y tiempo restante
  function initUrgency(){
    const now = Date.now();
    let state = JSON.parse(localStorage.getItem(URG_KEY) || 'null');
    if(!state || now - state.startTs > 1000 * 60 * 60 * 24){
      // Reinicia la urgencia cada 24 horas
      state = { spots: 12, startTs: now, duration: 1000*60*60*6 };
      localStorage.setItem(URG_KEY, JSON.stringify(state));
    }
    return state;
  }

  let urgencyState = initUrgency();

  // Formatea milisegundos en hh:mm:ss
  function formatTime(ms){
    const s = Math.max(0, Math.floor(ms/1000));
    const hh = String(Math.floor(s/3600)).padStart(2,'0');
    const mm = String(Math.floor((s%3600)/60)).padStart(2,'0');
    const ss = String(s%60).padStart(2,'0');
    return `${hh}:${mm}:${ss}`;
  }

  // Actualiza la visualización de la barra de urgencia con plazas y tiempo restante
  function renderUrgency(){
    const now = Date.now();
    const end = urgencyState.startTs + urgencyState.duration;
    const remaining = Math.max(0, end - now);
    urgencyText.textContent = `Quedan ${urgencyState.spots} plazas con descuento — oferta termina en ${formatTime(remaining)}`;

    // Cuando el tiempo se acaba, reduce las plazas y reinicia el temporizador
    if(remaining <= 0){
      if(urgencyState.spots > 1) urgencyState.spots = Math.max(1, urgencyState.spots - Math.floor(Math.random()*2+1));
      urgencyState.startTs = now;
      urgencyState.duration = 1000*60*30;
      localStorage.setItem(URG_KEY, JSON.stringify(urgencyState));
    }
  }

  // Reduce progresivamente el número de plazas disponibles cada cierto tiempo
  function decaySpots(){
    if(urgencyState.spots > 1){
      urgencyState.spots -= 1;
      localStorage.setItem(URG_KEY, JSON.stringify(urgencyState));
      renderUrgency();
    }
  }

  renderUrgency();
  let urgencyInterval = setInterval(renderUrgency, 1000);
  let decayInterval = setInterval(decaySpots, 90*1000);

  // Evento para redirigir al usuario a la sección de suscripciones al elegir urgencia
  urgencyChoose.addEventListener('click', () => {
    document.querySelector('a[href="#subscripciones"]').scrollIntoView({behavior:'smooth'});
    flashToast('Redirigido a planes — elige tu opción', 'success');
  });

  // Eventos para los botones de elegir plan: muestran un toast y desplazan al formulario
  document.querySelectorAll('.plan-choose').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const plan = e.currentTarget.dataset.plan || 'Plan';
      flashToast(`${plan} seleccionado — completa el formulario para finalizar`, 'success');
      document.getElementById('contacto').scrollIntoView({behavior:'smooth'});
    });
  });

  // Scrollspy: resalta el link activo del navbar según la sección visible
  const navLinks = document.querySelectorAll('.navbar .nav-link');
  const sections = Array.from(navLinks)
    .map(link => document.querySelector(link.getAttribute('href')))
    .filter(Boolean);

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      const id = entry.target.id;
      const link = document.querySelector(`.navbar .nav-link[href="#${id}"]`);
      if(entry.isIntersecting){
        navLinks.forEach(n => n.classList.remove('active'));
        if(link) link.classList.add('active');
      }
    });
  }, { threshold: 0.10, rootMargin: "-80px 0px 0px 0px" });

  sections.forEach(s => observer.observe(s));

  // Scroll suave al hacer click en un link del navbar y cierre automático del menú responsive
  navLinks.forEach(a => a.addEventListener('click', (e) => {
    e.preventDefault();
    const target = document.querySelector(a.getAttribute('href'));
    if(target) target.scrollIntoView({behavior:'smooth'});
    const navbarCollapse = document.getElementById('navMenu');
    if(navbarCollapse.classList.contains('show')){
      const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse) || new bootstrap.Collapse(navbarCollapse);
      bsCollapse.hide();
    }
  }));

  // Validación y envío simulado del formulario de contacto
  const contactForm = document.getElementById('contactForm');
  contactForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('nameInput').value.trim();
    const email = document.getElementById('emailInput').value.trim();
    const message = document.getElementById('messageInput').value.trim();

    if(!name || !email || !message){
      flashToast('Por favor completa todos los campos.', 'error');
      return;
    }
    if(!validateEmail(email)){
      flashToast('Ingresa un correo válido.', 'error');
      return;
    }

    flashToast('Mensaje enviado correctamente. Te contactaremos pronto.', 'success');
    contactForm.reset();
  });

  // Valida formato básico de correo electrónico
  function validateEmail(email){
    return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email);
  }

  // Función para mostrar notificaciones tipo toast en pantalla
  const toastContainer = document.getElementById('toastContainer');
  function flashToast(text, type='info', ttl = 4000){
    const t = document.createElement('div');
    t.className = `toast ${type==='success'? 'success' : type==='error'? 'error' : ''}`;
    t.textContent = text;
    toastContainer.appendChild(t);
    setTimeout(()=>{ t.style.opacity = '0'; t.style.transform = 'translateY(10px)'; }, ttl-600);
    setTimeout(()=>{ t.remove(); }, ttl);
  }

  // Validación y simulación de login desde hero section
  const loginForm = document.getElementById('loginForm');
  loginForm.addEventListener('submit', (e)=>{
    e.preventDefault();
    const email = document.getElementById('emailHero').value.trim();
    if(!email || !validateEmail(email)){
      flashToast('Ingresa un correo válido para iniciar sesión.', 'error');
      return;
    }
    flashToast('Iniciando sesión... (simulado)', 'success');
  });

  // Pausa y reanuda intervalos de urgencia cuando la pestaña está oculta o visible
  document.addEventListener('visibilitychange', () => {
    if(document.hidden){
      clearInterval(urgencyInterval);
      clearInterval(decayInterval);
    } else {
      renderUrgency();
      urgencyInterval = setInterval(renderUrgency, 1000);
      decayInterval = setInterval(decaySpots, 90*1000);
    }
  });

});
