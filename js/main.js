
// Minimal JS to handle fake auth form submissions and demo interactions.
document.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.getElementById('loginForm');
  const signupForm = document.getElementById('signupForm');

  function fakeSubmit(ev) {
    ev.preventDefault();
    const btn = ev.target.querySelector('button[type="submit"]');
    const old = btn.innerText;
    btn.innerText = 'Processing...';
    setTimeout(()=> {
      btn.innerText = old;
      alert('This is a demo. In a real app you would be redirected after successful authentication.');
    }, 900);
  }

  if (loginForm) loginForm.addEventListener('submit', fakeSubmit);
  if (signupForm) signupForm.addEventListener('submit', fakeSubmit);
});
