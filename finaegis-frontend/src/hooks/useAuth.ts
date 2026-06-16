"use client"

import { useState, useEffect, useCallback } from "react"
import { auth } from "@/lib/api"
import { client } from "@/lib/apollo-client"

interface AuthUser {
  id: number
  email: string
  name?: string
  [key: string]: any
}

export function useAuth() {
  const [user, setUser] = useState<AuthUser | null>(null)
  const [loading, setLoading] = useState(true)
  const [token, setToken] = useState<string | null>(null)

  useEffect(() => {
    const saved = localStorage.getItem("auth_token")
    if (saved) {
      setToken(saved)
      auth.me()
        .then((res) => {
          const u = res.data
          setUser(u)
          localStorage.setItem("auth_user", JSON.stringify(u))
        })
        .catch(() => {
          localStorage.removeItem("auth_token")
          localStorage.removeItem("auth_user")
        })
        .finally(() => setLoading(false))
    } else {
      setLoading(false)
    }
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    const res = await auth.login(email, password)
    const t = res.token
    const u = res.user
    localStorage.setItem("auth_token", t)
    localStorage.setItem("auth_user", JSON.stringify(u))
    setToken(t)
    setUser(u)
    await client.resetStore()
    return u
  }, [])

  const register = useCallback(async (data: {
    email: string
    password: string
    password_confirmation: string
    first_name?: string
    last_name?: string
  }) => {
    const res = await auth.register(data)
    const t = res.token
    const u = res.user
    localStorage.setItem("auth_token", t)
    localStorage.setItem("auth_user", JSON.stringify(u))
    setToken(t)
    setUser(u)
    await client.resetStore()
    return u
  }, [])

  const logout = useCallback(async () => {
    try {
      await auth.logout()
    } catch {}
    localStorage.removeItem("auth_token")
    localStorage.removeItem("auth_user")
    setToken(null)
    setUser(null)
    await client.resetStore()
  }, [])

  return { user, token, loading, login, register, logout, isAuthenticated: !!token }
}
