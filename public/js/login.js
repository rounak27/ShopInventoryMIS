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

  function validateStoredToken(token) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: `${Config.apiBase}/test?_=${Date.now()}`,
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        },
        success: () => resolve(true),
        error: () => reject(new Error('Invalid token'))
      });
    });
  }

  const token = localStorage.getItem('token');
  if (token) {
    validateStoredToken(token)
      .then(() => {
        window.location.href = `${baseURL}/dashboard`;
      })
      .catch(() => {
        localStorage.removeItem('token');
      });
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