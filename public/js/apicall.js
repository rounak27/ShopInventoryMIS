const baseURL=$('#baseUrl').text();
const Config = {
  apiBase:      baseURL+ '/api/v1',
  lowStockThresh: 10,
  currency:       'Rs.',
  dateFormat:     'YYYY-MM-DD',
  itemsPerPage:   10,
};
const API = {

  getToken() {
    return localStorage.getItem('token');
  },

  get(endpoint, cb) {
    $.ajax({
      url: Config.apiBase + endpoint,
      method: 'GET',
      headers: {
        'Authorization': 'Bearer ' + API.getToken()
      },
      success: cb,
      error: (xhr) => toast(xhr.responseJSON?.message || 'Server error', 'danger'),
    });
  },

  post(endpoint, data) {
    return new Promise((resolve, reject) => {
        $.ajax({
        url: Config.apiBase + endpoint,
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        headers: { 
          'Authorization': 'Bearer ' + API.getToken(),
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
        },
        success: (res) => resolve(res),
        error: (xhr) => reject(xhr.responseJSON || { message: 'Server error' }),
        });
    });
},

  put(endpoint, data, cb) {
    $.ajax({
      url: Config.apiBase + endpoint,
      method: 'PUT',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: {
        'Authorization': 'Bearer ' + API.getToken(),
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: cb,
      error: (xhr) => toast(xhr.responseJSON?.message || 'Server error', 'danger'),
    });
  },

  delete(endpoint, cb) {
    $.ajax({
      url: Config.apiBase + endpoint,
      method: 'DELETE',
      headers: {
        'Authorization': 'Bearer ' + API.getToken(),
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: cb,
      error: (xhr) => toast(xhr.responseJSON?.message || 'Server error', 'danger'),
    });
  }

};