const menuButton = document.querySelector('.menu-button');
const navigation = document.querySelector('.main-nav');
const form = document.querySelector('#trip-form');
const destination = document.querySelector('#destination');
const travelDate = form.querySelector('input[type="date"]');
const toast = document.querySelector('.toast');
let toastTimer;

// Function to show toast messages(toast messages are small notifications that appear temporarily on the screen to provide feedback to the user)
const showToast = (message) => {
  clearTimeout(toastTimer);
  toast.textContent = message;
  toast.classList.add('show');
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3200);
};

const closeNavigation = () => {
  navigation.classList.remove('open');
  menuButton.setAttribute('aria-expanded', 'false');
};

menuButton.addEventListener('click', () => {
  const isOpen = navigation.classList.toggle('open');
  menuButton.setAttribute('aria-expanded', isOpen);
});

navigation.querySelectorAll('a').forEach((link) => {
  link.addEventListener('click', closeNavigation);
});

document.addEventListener('click', (event) => {
  if (navigation.classList.contains('open') && !navigation.contains(event.target) && event.target !== menuButton) {
    closeNavigation();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    closeNavigation();
  }
});

travelDate.min = new Date().toISOString().split('T')[0];

document.querySelectorAll('.destination-card').forEach((card) => {
  const cardTitle = card.querySelector('h3').textContent;
  card.setAttribute('tabindex', '0');
  card.setAttribute('role', 'button');
  card.setAttribute('aria-label', `Plan a trip to ${cardTitle}`);

  const chooseDestination = () => {
    destination.value = cardTitle;
    document.querySelector('#search').scrollIntoView({ behavior: 'smooth', block: 'center' });
    destination.focus();
    showToast(`${cardTitle} added to your trip search.`);
  };

  card.addEventListener('click', chooseDestination);
  card.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      chooseDestination();
    }
  });
});

form.addEventListener('submit', (event) => {
  event.preventDefault();
  const place = destination.value.trim();

  if (!place) {
    destination.focus();
    showToast('Tell us where you would like to go first.');
    return;
  }

  showToast(`Great choice — we'll help plan ${place}.`);
});

document.querySelector('#year').textContent = new Date().getFullYear();
