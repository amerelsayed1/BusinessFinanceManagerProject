export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000/api';

async function apiRequest(path, options = {}) {
  const url = `${API_BASE_URL}${path}`;
  const defaultOptions = {
    headers: {
      'Accept': 'application/json',
      ...(options.headers || {}),
    },
    ...options,
  };

  const response = await fetch(url, defaultOptions);

  let text;
  try {
    text = await response.text();
  } catch {
    text = '';
  }

  let data = null;
  if (text) {
    try {
      data = JSON.parse(text);
    } catch {
      data = text;
    }
  }

  if (!response.ok) {
    const error = new Error(`API error ${response.status}`);
    error.status = response.status;
    error.body = data;
    throw error;
  }

  return data;
}

export const apiGet = (path) => apiRequest(path, { method: 'GET' });

export const apiPost = (path, body) =>
  apiRequest(path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body ?? {}),
  });

export const apiDelete = (path) =>
  apiRequest(path, {
    method: 'DELETE',
  });

export const apiPatch = (path, body) =>
  apiRequest(path, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body ?? {}),
  });
