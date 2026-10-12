const menuButton = document.querySelector('.menu-button');
const navigation = document.querySelector('.main-nav');
const form = document.querySelector('#trip-form');
const destination = document.querySelector('#destination');
const toast = document.querySelector('.toast');

menuButton.addEventListener('click', () => {
  const isOpen = navigation.classList.toggle('open');
  menuButton.setAttribute('aria-expanded', isOpen);
});

form.addEventListener('submit', (event) => {
  event.preventDefault();
  const place = destination.value.trim() || 'your chosen destination';
  toast.textContent = `Great choice — we'll help plan ${place}.`;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3200);
});

document.querySelector('#year').textContent = new Date().getFullYear();
