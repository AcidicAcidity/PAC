const API_URL = '/api.php'

export async function api(method, params = {}) {
  const token = localStorage.getItem('access_token')
  const headers = { 'Content-Type': 'application/json' }
  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  const response = await fetch(API_URL, {
    method: 'POST',
    headers,
    body: JSON.stringify({ method, params }),
  })

  const data = await response.json()

  if (data.error) {
    const err = new Error(data.error)
    err.status = response.status
    throw err
  }

  return data.result
}

export function getWsUrl() {
  const token = localStorage.getItem('access_token')
  const host = window.location.hostname
  const port = import.meta.env.DEV ? '8081' : '8081'
  return `ws://${host}:${port}?token=${encodeURIComponent(token || '')}`
}
