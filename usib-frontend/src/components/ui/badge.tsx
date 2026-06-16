"use client"

import * as React from "react"
import { cn } from "@/lib/utils"

const Badge = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement> & {
  variant?: "default" | "secondary" | "success" | "warning" | "danger" | "outline"
}>(({ className, variant = "default", ...props }, ref) => {
  const variants = {
    default: "bg-usib-100 text-usib-700 dark:bg-usib-800 dark:text-usib-200",
    secondary: "bg-navy-100 text-navy-700 dark:bg-navy-800 dark:text-navy-200",
    success: "bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200",
    warning: "bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200",
    danger: "bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200",
    outline: "border text-foreground",
  }
  return (
    <div
      ref={ref}
      className={cn(
        "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors",
        variants[variant],
        className
      )}
      {...props}
    />
  )
})
Badge.displayName = "Badge"

export { Badge }
