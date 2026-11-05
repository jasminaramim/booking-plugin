declare global {
  interface Window {
    nmbpAdminConfig?: {
      restUrl: string;
      nonce: string;
      ajaxUrl: string;
      siteUrl: string;
      pluginUrl: string;
      version: string;
      currentUser: {
        id: number;
        name: string;
        email: string;
        roles: string[];
        isSuperAdmin: boolean;
        doctorId: number | null;
      };
      generalSettings: any;
      designSettings: any;
    };
  }
}

const config = window.nmbpAdminConfig || {
  restUrl: '/wp-json/nmbp/v1/',
  nonce: '',
  currentUser: {
    id: 1,
    name: 'Administrator',
    email: 'admin@example.com',
    roles: ['administrator'],
    isSuperAdmin: true,
    doctorId: null,
  },
};

export const api = {
  get: async <T>(endpoint: string, params?: Record<string, any>): Promise<T> => {
    let url = config.restUrl + endpoint.replace(/^\//, '');
    if (params) {
      const query = new URLSearchParams();
      Object.entries(params).forEach(([k, v]) => {
        if (v !== undefined && v !== null && v !== '') {
          query.append(k, String(v));
        }
      });
      const qs = query.toString();
      if (qs) url += (url.includes('?') ? '&' : '?') + qs;
    }

    const res = await fetch(url, {
      method: 'GET',
      headers: {
        'X-WP-Nonce': config.nonce,
        'Content-Type': 'application/json',
      },
    });

    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.message || 'API request failed');
    }
    return data.data;
  },

  post: async <T>(endpoint: string, body?: any): Promise<T> => {
    const url = config.restUrl + endpoint.replace(/^\//, '');
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'X-WP-Nonce': config.nonce,
        'Content-Type': 'application/json',
      },
      body: body ? JSON.stringify(body) : undefined,
    });

    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.message || 'API request failed');
    }
    return data.data;
  },

  put: async <T>(endpoint: string, body?: any): Promise<T> => {
    const url = config.restUrl + endpoint.replace(/^\//, '');
    const res = await fetch(url, {
      method: 'PUT',
      headers: {
        'X-WP-Nonce': config.nonce,
        'Content-Type': 'application/json',
      },
      body: body ? JSON.stringify(body) : undefined,
    });

    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.message || 'API request failed');
    }
    return data.data;
  },

  delete: async <T>(endpoint: string): Promise<T> => {
    const url = config.restUrl + endpoint.replace(/^\//, '');
    const res = await fetch(url, {
      method: 'DELETE',
      headers: {
        'X-WP-Nonce': config.nonce,
        'Content-Type': 'application/json',
      },
    });

    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.message || 'API request failed');
    }
    return data.data;
  },
};

