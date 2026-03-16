$(function () {
    
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
        //   toast(res.message || 'Login failed', 'danger');
        alert(res.message || 'Login failed');
        
          return;
        }

        /* store token */
        localStorage.setItem('token', res.token);
        alert('Login successful');
        // toast('Login successful', 'success');

        setTimeout(() => {
          window.location.href = baseURL+'/dashboard';
        }, 600);

      })
      .catch(err => {
        console.error(err);
        alert('Server error occurred');
      });

  });

});