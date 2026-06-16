"use client"

import { useState, useEffect } from "react"
import Link from "next/link"
import { motion, AnimatePresence } from "framer-motion"
import { Menu, X, ChevronDown } from "lucide-react"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"

const navLinks = [
  { label: "Personal Banking", href: "/personal-banking" },
  { label: "Business Banking", href: "/business-banking" },
  { label: "Loans", href: "/loans" },
  { label: "Cards", href: "/cards" },
  { label: "International", href: "/international" },
  { label: "Security", href: "/security" },
]

export default function Header() {
  const [scrolled, setScrolled] = useState(false)
  const [mobileOpen, setMobileOpen] = useState(false)

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 20)
    window.addEventListener("scroll", onScroll)
    return () => window.removeEventListener("scroll", onScroll)
  }, [])

  useEffect(() => {
    if (mobileOpen) document.body.style.overflow = "hidden"
    else document.body.style.overflow = ""
    return () => { document.body.style.overflow = "" }
  }, [mobileOpen])

  return (
    <header
      className={cn(
        "fixed top-0 left-0 right-0 z-50 transition-all duration-300",
        scrolled
          ? "bg-white/95 dark:bg-navy-900/95 backdrop-blur-md shadow-sm"
          : "bg-transparent"
      )}
    >
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex h-20 items-center justify-between">
          <Link href="/" className="flex items-center gap-2">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-500">
              <span className="text-lg font-bold text-navy-900">U</span>
            </div>
            <div className="hidden sm:block">
              <p className={cn(
                "text-sm font-bold leading-tight tracking-tight font-display",
                scrolled ? "text-navy-900 dark:text-white" : "text-white"
              )}>
                United Standard
              </p>
              <p className={cn(
                "text-[10px] font-medium tracking-widest uppercase",
                scrolled ? "text-navy-500 dark:text-navy-300" : "text-white/70"
              )}>
                International Bank
              </p>
            </div>
          </Link>

          <nav className="hidden lg:flex items-center gap-1">
            {navLinks.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className={cn(
                  "px-3 py-2 rounded-lg text-sm font-medium transition-colors",
                  scrolled
                    ? "text-navy-700 dark:text-navy-200 hover:text-navy-900 dark:hover:text-white hover:bg-navy-50 dark:hover:bg-navy-800"
                    : "text-white/80 hover:text-white hover:bg-white/10"
                )}
              >
                {link.label}
              </Link>
            ))}
            <Link
              href="/support"
              className={cn(
                "px-3 py-2 rounded-lg text-sm font-medium transition-colors",
                scrolled
                  ? "text-navy-700 dark:text-navy-200 hover:text-navy-900 dark:hover:text-white hover:bg-navy-50 dark:hover:bg-navy-800"
                  : "text-white/80 hover:text-white hover:bg-white/10"
              )}
            >
              Support
            </Link>
          </nav>

          <div className="hidden lg:flex items-center gap-3">
            <Button
              variant={scrolled ? "ghost" : "ghost"}
              className={cn(
                "text-sm font-medium",
                scrolled
                  ? "text-navy-700 dark:text-navy-200"
                  : "text-white/80 hover:text-white"
              )}
              asChild
            >
              <Link href="/login">Sign In</Link>
            </Button>
            <Button variant="accent" size="sm" className="font-semibold" asChild>
              <Link href="/register">Create Account</Link>
            </Button>
          </div>

          <button
            onClick={() => setMobileOpen(true)}
            className={cn(
              "lg:hidden p-2 rounded-lg transition-colors",
              scrolled
                ? "text-navy-700 dark:text-navy-200"
                : "text-white"
            )}
          >
            <Menu className="h-6 w-6" />
          </button>
        </div>
      </div>

      <AnimatePresence>
        {mobileOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-50 lg:hidden"
          >
            <div className="fixed inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setMobileOpen(false)} />
            <motion.div
              initial={{ x: "100%" }}
              animate={{ x: 0 }}
              exit={{ x: "100%" }}
              transition={{ type: "spring", damping: 25, stiffness: 300 }}
              className="fixed right-0 top-0 bottom-0 w-full max-w-sm bg-white dark:bg-navy-900 shadow-xl"
            >
              <div className="flex items-center justify-between p-4 border-b border-gray-100 dark:border-navy-800">
                <div className="flex items-center gap-2">
                  <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gold-500">
                    <span className="text-sm font-bold text-navy-900">U</span>
                  </div>
                  <span className="text-sm font-bold font-display text-navy-900 dark:text-white">USIB</span>
                </div>
                <button
                  onClick={() => setMobileOpen(false)}
                  className="p-2 rounded-lg text-navy-500 hover:bg-gray-100 dark:hover:bg-navy-800"
                >
                  <X className="h-5 w-5" />
                </button>
              </div>
              <nav className="p-4 space-y-1">
                {navLinks.map((link) => (
                  <Link
                    key={link.href}
                    href={link.href}
                    onClick={() => setMobileOpen(false)}
                    className="flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-navy-700 dark:text-navy-200 hover:bg-navy-50 dark:hover:bg-navy-800 transition-colors"
                  >
                    {link.label}
                    <ChevronDown className="h-4 w-4 -rotate-90 text-navy-400" />
                  </Link>
                ))}
                <Link
                  href="/support"
                  onClick={() => setMobileOpen(false)}
                  className="flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-navy-700 dark:text-navy-200 hover:bg-navy-50 dark:hover:bg-navy-800 transition-colors"
                >
                  Support
                  <ChevronDown className="h-4 w-4 -rotate-90 text-navy-400" />
                </Link>
              </nav>
              <div className="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-100 dark:border-navy-800 bg-white dark:bg-navy-900 space-y-3">
                <Button variant="outline" className="w-full text-sm font-medium" asChild>
                  <Link href="/login">Sign In</Link>
                </Button>
                <Button variant="accent" className="w-full text-sm font-semibold" asChild>
                  <Link href="/register">Create Account</Link>
                </Button>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </header>
  )
}
