/* ============================================================
   QUINNIX SOFTWARE — site.js
   ============================================================ */

/* ============================================================
   SITE CONFIGURATION
   Update these values when contact info and links are confirmed.
   ============================================================ */
const CONFIG = {
  // Social / external links — replace placeholders before launch
  github:    '',   // e.g. 'https://github.com/yourhandle'
  socials:   [],   // array of { label, url } objects for additional social links
};

/* ============================================================
   DYNAMIC COPYRIGHT YEAR
   ============================================================ */
document.querySelectorAll('[data-year]').forEach(el => {
  el.textContent = new Date().getFullYear();
});

/* ============================================================
   STICKY HEADER
   ============================================================ */
const header = document.querySelector('[data-header]');
if (header) {
  const onScroll = () => {
    header.classList.toggle('is-scrolled', window.scrollY > 10);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

/* ============================================================
   MOBILE NAVIGATION
   ============================================================ */
const menuButton = document.querySelector('[data-menu-button]');
const nav        = document.querySelector('[data-nav]');

if (menuButton && nav) {
  menuButton.addEventListener('click', () => {
    const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
    menuButton.setAttribute('aria-expanded', String(!isOpen));
    nav.classList.toggle('is-open', !isOpen);
    document.body.style.overflow = isOpen ? '' : 'hidden';
  });

  // Close nav when a link is clicked
  nav.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      menuButton.setAttribute('aria-expanded', 'false');
      nav.classList.remove('is-open');
      document.body.style.overflow = '';
    });
  });

  // Close nav on Escape
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && nav.classList.contains('is-open')) {
      menuButton.setAttribute('aria-expanded', 'false');
      nav.classList.remove('is-open');
      document.body.style.overflow = '';
      menuButton.focus();
    }
  });
}

/* ============================================================
   ACTIVE NAV LINK (scroll spy)
   ============================================================ */
const sections = document.querySelectorAll('section[id]');
const navLinks  = document.querySelectorAll('.site-nav a');

if (sections.length && navLinks.length) {
  const spy = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const id = entry.target.getAttribute('id');
        navLinks.forEach(link => {
          link.classList.toggle('is-active', link.getAttribute('href') === '#' + id);
        });
      }
    });
  }, { rootMargin: '-50% 0px -50% 0px' });

  sections.forEach(s => spy.observe(s));
}

/* ============================================================
   3D CARD TILT
   ============================================================ */
document.querySelectorAll('.project-card').forEach(card => {
  card.addEventListener('mousemove', e => {
    const rect = card.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width  - 0.5;
    const y = (e.clientY - rect.top)  / rect.height - 0.5;
    card.style.transform = `perspective(700px) rotateY(${x * 10}deg) rotateX(${-y * 7}deg) translateZ(6px)`;
  });
  card.addEventListener('mouseleave', () => {
    card.style.transform = '';
  });
});

/* ============================================================
   CONTACT FORM
   ============================================================ */
const form     = document.getElementById('contact-form');
const feedback = document.getElementById('form-feedback');
const submit   = document.getElementById('contact-submit');

if (form && feedback && submit) {

  // Clear per-field error on input
  form.querySelectorAll('input, select, textarea').forEach(field => {
    field.addEventListener('input', () => clearFieldError(field));
    field.addEventListener('change', () => clearFieldError(field));
  });

  form.addEventListener('submit', async e => {
    e.preventDefault();

    if (!clientValidate()) return;

    // Disable button while submitting
    submit.setAttribute('aria-busy', 'true');
    submit.disabled = true;
    feedback.className = 'form-feedback';
    feedback.textContent = '';

    try {
      const data = new FormData(form);
      const res  = await fetch('contact.php', {
        method:  'POST',
        body:    data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });

      let json;
      try {
        json = await res.json();
      } catch {
        throw new Error('Unexpected server response. Please try again.');
      }

      if (json.success) {
        feedback.className = 'form-feedback is-success';
        feedback.innerHTML = '<strong class="feedback-title">Message sent!</strong>'
          + '<p>Thanks for reaching out. Your message has been received, and I\'ll get back to you as soon as I can.</p>';
        form.reset();
      } else {
        feedback.className   = 'form-feedback is-error';
        feedback.textContent = json.message || 'Something went wrong. Please try again.';
      }

    } catch (err) {
      feedback.className  = 'form-feedback is-error';
      feedback.textContent = err.message || 'Could not send message. Please try again later.';
    } finally {
      submit.removeAttribute('aria-busy');
      submit.disabled = false;
      feedback.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  });

  function clientValidate() {
    let valid = true;

    const name    = form.querySelector('[name="name"]');
    const email   = form.querySelector('[name="email"]');
    const message = form.querySelector('[name="message"]');

    if (!name.value.trim()) {
      setFieldError(name, 'Please enter your name.');
      valid = false;
    }

    if (!email.value.trim()) {
      setFieldError(email, 'Please enter your email address.');
      valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
      setFieldError(email, 'Please enter a valid email address.');
      valid = false;
    }

    if (!message.value.trim() || message.value.trim().length < 10) {
      setFieldError(message, 'Please enter a message (at least 10 characters).');
      valid = false;
    }

    return valid;
  }

  function setFieldError(field, msg) {
    const group = field.closest('.form-group');
    if (!group) return;
    group.classList.add('has-error');
    let err = group.querySelector('.field-error');
    if (!err) {
      err = document.createElement('span');
      err.className = 'field-error';
      err.setAttribute('role', 'alert');
      field.after(err);
    }
    err.textContent = msg;
  }

  function clearFieldError(field) {
    const group = field.closest('.form-group');
    if (!group) return;
    group.classList.remove('has-error');
    const err = group.querySelector('.field-error');
    if (err) err.remove();
  }
}
