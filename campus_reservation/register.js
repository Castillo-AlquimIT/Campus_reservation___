    const form = document.getElementById('registerForm');
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        name: form.name.value.trim(),
        email: form.email.value.trim(),
        password: form.password.value,
        role: form.role.value
      };
      const res = await fetch('register.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      document.getElementById('regMsg').textContent = res.ok ? data.message : (data.error || 'Error');
      if (res.ok) setTimeout(() => window.location.href = 'login.html', 1500);
    });

    // Redirect to login.html
    document.getElementById('loginBtn').addEventListener('click', () => {
      window.location.href = 'login.html';
    });