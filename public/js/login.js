$(function () {
  function toast(msg, type = 'success') {
    const typeMap = {
      success: 'success',
      danger: 'error',
      warning: 'warning',
      info: 'info'
    };

    const toastrType = typeMap[type] || 'info';
    toastr[toastrType](msg);
  }

  const token = localStorage.getItem('token');
  if (token) {
    window.location.href = `${baseURL}/dashboard`;
    return;
  }

  const params = new URLSearchParams(window.location.search);
  if (params.get('auth') === 'expired') {
    toast('Your session has expired. Please login again.', 'warning');
    params.delete('auth');
    const query = params.toString();
    const cleanPath = `${window.location.pathname}${query ? `?${query}` : ''}`;
    window.history.replaceState({}, document.title, cleanPath);
  }

  if (params.get('auth') === 'required') {
    toast('You must login to view the page.', 'warning');
    params.delete('auth');
    const query = params.toString();
    const cleanPath = `${window.location.pathname}${query ? `?${query}` : ''}`;
    window.history.replaceState({}, document.title, cleanPath);
  }

    
  $('#loginForm').on('submit', function (e) {
    e.preventDefault();

    const username = $('#username').val().trim();
    const password = $('#password').val().trim();

    if (!username || !password) {
      toast('Enter username and password', 'warning');
      return;
    }

    const payload = {
      username: username,
      password: password
    };

    API.post('/auth/login', payload)
      .then(res => {

        if (!res.success) {
          toast(res.message || 'Login failed', 'danger');
        
          return;
        }

        /* store token */
        localStorage.setItem('token', res.token);
        toast('Login successful', 'success');

        setTimeout(() => {
          window.location.href = baseURL+'/dashboard';
        }, 600);

      })
      .catch(err => {
        console.error(err);
        toast(err.message || 'Server error occurred', 'danger');
      });

  });

});