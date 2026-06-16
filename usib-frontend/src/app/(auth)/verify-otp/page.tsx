"use client"

import { useState, useEffect, useRef, useCallback } from "react"
import { useRouter } from "next/navigation"
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { api } from "@/lib/api"
import { auth } from "@/lib/auth"

const RESEND_COOLDOWN = 60

export default function VerifyOtpPage() {
  const router = useRouter()
  const [otp, setOtp] = useState<string[]>(Array(6).fill(""))
  const [error, setError] = useState("")
  const [loading, setLoading] = useState(false)
  const [timer, setTimer] = useState(RESEND_COOLDOWN)
  const [canResend, setCanResend] = useState(false)
  const inputRefs = useRef<(HTMLInputElement | null)[]>(Array(6).fill(null))

  const userEmail = typeof window !== "undefined" ? auth.getUser()?.email || "" : ""

  useEffect(() => {
    if (timer > 0) {
      const interval = setInterval(() => setTimer((t) => t - 1), 1000)
      return () => clearInterval(interval)
    }
    setCanResend(true)
  }, [timer])

  const handleChange = useCallback((index: number, value: string) => {
    if (!/^\d*$/.test(value)) return
    const digit = value.slice(-1)
    const newOtp = [...otp]
    newOtp[index] = digit
    setOtp(newOtp)
    setError("")
    if (digit && index < 5) {
      inputRefs.current[index + 1]?.focus()
    }
  }, [otp])

  const handleKeyDown = useCallback((index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === "Backspace" && !otp[index] && index > 0) {
      inputRefs.current[index - 1]?.focus()
    }
  }, [otp])

  const handlePaste = useCallback((e: React.ClipboardEvent<HTMLInputElement>) => {
    e.preventDefault()
    const pasted = e.clipboardData.getData("text").replace(/\D/g, "").slice(0, 6)
    if (!pasted) return
    const newOtp = Array(6).fill("")
    for (let i = 0; i < pasted.length; i++) {
      newOtp[i] = pasted[i]
    }
    setOtp(newOtp)
    const nextIndex = Math.min(pasted.length, 5)
    inputRefs.current[nextIndex]?.focus()
  }, [])

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    const code = otp.join("")
    if (code.length !== 6) {
      setError("Please enter the full 6-digit code")
      return
    }
    setLoading(true)
    setError("")
    try {
      const result = await api.verifyOtp(userEmail, code)
      if (result.verified && result.token) {
        auth.setToken(result.token)
        router.push("/dashboard")
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "Verification failed")
      setOtp(Array(6).fill(""))
      inputRefs.current[0]?.focus()
    } finally {
      setLoading(false)
    }
  }

  function handleResend() {
    setTimer(RESEND_COOLDOWN)
    setCanResend(false)
    setError("")
    setOtp(Array(6).fill(""))
  }

  return (
    <Card className="border-0 shadow-elevated bg-white/95 dark:bg-navy-800/95 backdrop-blur-sm">
      <CardHeader className="text-center pb-4">
        <CardTitle className="font-display text-2xl text-navy-900 dark:text-white">
          Verify Your Identity
        </CardTitle>
        <CardDescription className="text-navy-500/70 dark:text-navy-200/70">
          Enter the 6-digit code sent to your registered email
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-6">
          {error && (
            <div className="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400 text-center">
              {error}
            </div>
          )}
          <div className="flex items-center justify-center gap-2 sm:gap-3">
            {otp.map((digit, index) => (
              <input
                key={index}
                ref={(el) => { inputRefs.current[index] = el }}
                type="text"
                inputMode="numeric"
                maxLength={1}
                value={digit}
                onChange={(e) => handleChange(index, e.target.value)}
                onKeyDown={(e) => handleKeyDown(index, e)}
                onPaste={index === 0 ? handlePaste : undefined}
                autoFocus={index === 0}
                className="h-12 w-10 sm:h-14 sm:w-12 rounded-lg border border-navy-200 dark:border-navy-600 bg-white dark:bg-navy-700 text-center text-lg font-bold text-navy-900 dark:text-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/30 outline-none transition-all"
              />
            ))}
          </div>
          <Button
            type="submit"
            loading={loading}
            className="w-full h-11 bg-gold-500 hover:bg-gold-600 text-navy-900 font-semibold text-base"
          >
            {loading ? "Verifying..." : "Verify Code"}
          </Button>
          <div className="text-center">
            {canResend ? (
              <button
                type="button"
                onClick={handleResend}
                className="text-sm text-gold-600 dark:text-gold-400 hover:text-gold-700 dark:hover:text-gold-300 font-medium transition-colors"
              >
                Resend code
              </button>
            ) : (
              <p className="text-sm text-navy-500/60 dark:text-navy-200/60">
                Resend code in <span className="font-medium text-navy-700 dark:text-navy-100">{timer}s</span>
              </p>
            )}
          </div>
        </form>
      </CardContent>
    </Card>
  )
}
