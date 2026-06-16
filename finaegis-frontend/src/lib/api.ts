const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000"

function getToken(): string | null {
  if (typeof window === "undefined") return null
  return localStorage.getItem("auth_token")
}

export async function apiFetch<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const token = getToken()
  const res = await fetch(`${API_URL}${endpoint}`, {
    ...options,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  })
  if (!res.ok) {
    const err = await res.json().catch(() => ({ message: res.statusText }))
    throw new Error(err.message || `Request failed: ${res.status}`)
  }
  return res.json()
}

export const auth = {
  login: (email: string, password: string) =>
    apiFetch<{ token: string; user: any }>("/api/auth/login", {
      method: "POST",
      body: JSON.stringify({ email, password }),
    }),

  register: (data: { email: string; password: string; password_confirmation: string; first_name?: string; last_name?: string }) =>
    apiFetch<{ token: string; user: any }>("/api/auth/register", {
      method: "POST",
      body: JSON.stringify(data),
    }),

  logout: () =>
    apiFetch<void>("/api/auth/logout", { method: "POST" }),

  me: () =>
    apiFetch<{ data: any }>("/api/auth/me"),

  refresh: (token: string) =>
    apiFetch<{ token: string }>("/api/auth/refresh", {
      method: "POST",
      body: JSON.stringify({ refresh_token: token }),
    }),

  forgotPassword: (email: string) =>
    apiFetch<void>("/api/auth/forgot-password", {
      method: "POST",
      body: JSON.stringify({ email }),
    }),

  resetPassword: (data: { email: string; token: string; password: string; password_confirmation: string }) =>
    apiFetch<void>("/api/auth/reset-password", {
      method: "POST",
      body: JSON.stringify(data),
    }),
}
