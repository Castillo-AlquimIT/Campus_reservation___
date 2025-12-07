// Toggle between login and register
const loginTab = document.getElementById('loginTab');
const registerTab = document.getElementById('registerTab');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');

loginTab.addEventListener('click', () => {
  loginForm.classList.add('active');
  registerForm.classList.remove('active');
});
registerTab.addEventListener('click', () => {
  registerForm.classList.add('active');
  loginForm.classList.remove('active');
});

// Handle login
document.getElementById('login').addEventListener('submit', async e => {
  e.preventDefault();
  const email = e.target.email.value.trim();
  const password = e.target.password.value;
  const res = await fetch('login.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({email, password})
  });
  const data = await res.json();
  document.getElementById('loginMsg').textContent = res.ok ? data.message : (data.error || 'Login failed');
  if (res.ok) window.location.href = 'index.php';
});

// Handle register
document.getElementById('register').addEventListener('submit', async e => {
  e.preventDefault();
  const payload = {
    name: e.target.name.value.trim(),
    email: e.target.email.value.trim(),
    password: e.target.password.value,
    role: e.target.role.value
  };
  const res = await fetch('register.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  document.getElementById('regMsg').textContent = res.ok ? data.message : (data.error || 'Error');
  if (res.ok) setTimeout(() => window.location.href = 'auth.html', 1500);
});
