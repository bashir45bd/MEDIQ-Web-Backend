/**
 * MEDIQ Admin Web — Vanilla ES6 API Client
 */

export const API_BASE = window.location.port === '8000'
  ? 'http://localhost:8000/api/v1'
  : (window.location.origin + '/mediq/backend/public/api/v1');

export const AdminApi = {
  getToken() {
    return localStorage.getItem('mediq_admin_token') || '';
  },

  setToken(token) {
    localStorage.setItem('mediq_admin_token', token);
    document.cookie = `mediq_token=${token}; path=/; max-age=2592000; SameSite=Lax`;
  },

  clearToken() {
    localStorage.removeItem('mediq_admin_token');
    document.cookie = 'mediq_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
  },

  async request(endpoint, method = 'GET', body = null) {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    };

    const token = this.getToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    const config = { method, headers };
    if (body && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
      config.body = JSON.stringify(body);
    }

    try {
      const response = await fetch(`${API_BASE}${endpoint}`, config);
      const json = await response.json();

      if (!response.ok) {
        if (response.status === 401) {
          this.clearToken();
          window.location.href = 'login.php';
          return null;
        }
        throw new Error(json.error?.message || 'Admin API error');
      }

      return json;
    } catch (err) {
      console.error('Admin API Error:', err);
      throw err;
    }
  },

  get(endpoint) { return this.request(endpoint, 'GET'); },
  post(endpoint, body) { return this.request(endpoint, 'POST', body); },
  put(endpoint, body) { return this.request(endpoint, 'PUT', body); },
  delete(endpoint) { return this.request(endpoint, 'DELETE'); }
};

export const Api = AdminApi;
export default AdminApi;
