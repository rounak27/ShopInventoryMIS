<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory System Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"/>
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#0d6efd">
<link rel="apple-touch-icon" href="{{ asset('stocklogosmall.png') }}">
<style>
body{
  background:linear-gradient(135deg,#0f172a,#1e293b);
  height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  font-family:system-ui,-apple-system,Segoe UI,Roboto;
}

.login-card{
  width:420px;
  background:#ffffff;
  border-radius:14px;
  box-shadow:0 20px 50px rgba(0,0,0,.25);
  padding:40px;
}

.brand{
  text-align:center;
  margin-bottom:30px;
}

.brand-logo{
  width:200px;
  height:auto;
  display:block;
  margin:0 auto 10px;
}

.brand h3{
  font-weight:700;
  display:none;
  margin:10px 0 0 0;
}

.brand p{
  font-size:.9rem;
  color:#6b7280;
}

.form-control{
  height:44px;
}

.btn-login{
  height:44px;
  font-weight:600;
}

.footer{
  text-align:center;
  margin-top:20px;
  font-size:.8rem;
  color:#6b7280;
}
</style>
</head>
<body>
<div id="baseUrl" class="card d-none" > {{ url('/') }}</div>

<!-- PWA Install Button -->
<button id="pwaInstallBtn" onclick="triggerPwaInstall()" 
  style="position:fixed;top:12px;right:12px;display:block;padding:8px 14px;background:#0d6efd;color:white;border:none;border-radius:6px;font-size:.85rem;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.2);z-index:9999;transition:all .3s;">
  <i class="bi bi-download"></i> Install App
</button>

<div class="login-card">

  <div class="brand">
    <img src="{{ asset('stocklogosmall.png') }}" alt="Stock Logo" class="brand-logo">
    <h3>Inventory System</h3> 
    <p>Sign in to continue</p>
  </div>

  <form id="loginForm">

    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" id="username" class="form-control" placeholder="Enter username" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" id="password" class="form-control" placeholder="Enter password" required>
    </div>

    <div class="d-grid">
      <button type="submit" class="btn btn-primary btn-login">
        <i class="bi bi-box-arrow-in-right"></i>
        Login
      </button>
    </div>

  </form>

  <div class="footer">
    © 2026 Inventory Management System
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
// ─── PWA Service Worker Registration ───
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register("{{ asset('sw.js') }}")
    .then(() => console.log('✅ Service Worker Registered'))
    .catch(err => console.error('❌ SW failed:', err));
}

// ─── PWA Install Prompt Handler ───
let deferredPrompt = null;
const pwaInstallBtn = document.getElementById('pwaInstallBtn');
const isIos = /iPhone|iPad|iPod/i.test(navigator.userAgent);
const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
const isSecure = window.isSecureContext || ['localhost', '127.0.0.1'].includes(window.location.hostname);

if (pwaInstallBtn && !isStandalone) {
  pwaInstallBtn.style.display = 'block';
}

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  console.log('Install prompt is ready');
});

window.addEventListener('appinstalled', () => {
  console.log('App installed successfully');
  deferredPrompt = null;
});

function triggerPwaInstall() {
  if (deferredPrompt) {
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then((choiceResult) => {
      console.log('Install choice:', choiceResult.outcome);
      deferredPrompt = null;
    });
  } else {
    if (isStandalone) {
      alert('App is already installed on this device.');
      return;
    }

    if (!isSecure) {
      alert('Install prompt requires HTTPS (or localhost). Open this site over HTTPS to enable install prompt.');
      return;
    }

    if (isIos) {
      alert('On iPhone/iPad, use Safari Share menu > Add to Home Screen.');
      return;
    }

    alert('Install prompt is not ready yet. Reload once and interact with the page, or use browser menu > Install app.');
  }
}
</script>
<script src="{{ asset('js/apicall.js') }}"></script>
<script src="{{ asset('js/login.js') }}"></script>

</body>
</html>
