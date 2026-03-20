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
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
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
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register("{{ asset('sw.js') }}").catch(function (err) {
      console.error('Service worker registration failed:', err);
    });
  });
}
</script>
<script src="{{ asset('js/apicall.js') }}"></script>
<script src="{{ asset('js/login.js') }}"></script>

</body>
</html>
