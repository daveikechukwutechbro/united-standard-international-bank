"use client"

import * as React from "react"
import { X } from "lucide-react"
import { cn } from "@/lib/utils"

interface ToastProps {
  message: string
  type?: "success" | "error" | "info" | "warning"
  onClose: () => void
}

export function Toast({ message, type = "info", onClose }: ToastProps) {
  const colors = {
    success: "bg-green-50 border-green-200 text-green-800 dark:bg-green-900 dark:border-green-700 dark:text-green-200",
    error: "bg-red-50 border-red-200 text-red-800 dark:bg-red-900 dark:border-red-700 dark:text-red-200",
    info: "bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900 dark:border-blue-700 dark:text-blue-200",
    warning: "bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900 dark:border-yellow-700 dark:text-yellow-200",
  }
  return (
    <div className={cn("fixed bottom-4 right-4 z-50 flex items-center gap-3 rounded-lg border px-4 py-3 shadow-elevated", colors[type])}>
      <p className="text-sm font-medium">{message}</p>
      <button onClick={onClose} className="shrink-0 rounded-md p-1 hover:bg-black/5">
        <X className="h-4 w-4" />
      </button>
    </div>
  )
}

// Simple toast hook
export function useToast() {
  const [toast, setToast] = React.useState<{ message: string; type: ToastProps["type"] } | null>(null)

  const show = React.useCallback((message: string, type: ToastProps["type"] = "info") => {
    setToast({ message, type })
  }, [])

  const hide = React.useCallback(() => {
    setToast(null)
  }, [])

  const ToastComponent = toast ? (
    <Toast message={toast.message} type={toast.type} onClose={hide} />
  ) : null

  return { show, hide, Toast: ToastComponent }
}
