"use client"

import { useState, useEffect } from "react"
import Link from "next/link"
import { usePathname, useRouter } from "next/navigation"
import { motion, AnimatePresence } from "framer-motion"
import {
  LayoutDashboard, CreditCard, ArrowUpDown, Download, Upload,
  Landmark, RefreshCw, Wallet, FileText, Headphones,
  Settings, Shield, Bell, Menu, X, Search, ChevronDown,
  LogOut, User, Building2, Home, ArrowRightLeft,
  CircleDollarSign, Sliders
} from "lucide-react"
import { cn } from "@/lib/utils"
import { auth } from "@/lib/auth"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Avatar, AvatarFallback } from "@radix-ui/react-avatar"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@radix-ui/react-dropdown-menu"

interface NavItem {
  label: string
  href: string
  icon: React.ElementType
  badge?: number
}

const sidebarNav: NavItem[] = [
  { label: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
  { label: "Accounts", href: "/dashboard/accounts", icon: Wallet },
  { label: "Transfers", href: "/dashboard/transfers", icon: ArrowUpDown },
  { label: "Deposit", href: "/dashboard/deposit", icon: Download },
  { label: "Withdraw", href: "/dashboard/withdraw", icon: Upload },
  { label: "Loans", href: "/dashboard/loans", icon: Landmark },
  { label: "Exchange", href: "/dashboard/exchange", icon: RefreshCw },
  { label: "Cards", href: "/dashboard/cards", icon: CreditCard },
  { label: "Statements", href: "/dashboard/statements", icon: FileText },
  { label: "Support", href: "/dashboard/support", icon: Headphones },
  { label: "Settings", href: "/dashboard/settings", icon: Settings },
  { label: "Security", href: "/dashboard/security", icon: Shield },
  { label: "Admin", href: "/dashboard/admin", icon: Sliders },
]

const bottomNav: NavItem[] = [
  { label: "Home", href: "/dashboard", icon: Home },
  { label: "Accounts", href: "/dashboard/accounts", icon: Wallet },
  { label: "Transfer", href: "/dashboard/transfers", icon: ArrowRightLeft },
  { label: "Deposit", href: "/dashboard/deposit", icon: CircleDollarSign },
  { label: "More", href: "#", icon: ChevronDown },
]

export default function AppShell({ children }: { children: React.ReactNode }) {
  const pathname = usePathname()
  const router = useRouter()
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const [mounted, setMounted] = useState(false)
  const user = auth.getUser()

  useEffect(() => { setMounted(true) }, [])

  if (!mounted) return null

  const initials = user
    ? `${user.firstName[0]}${user.lastName[0]}`
    : "U"

  const handleLogout = () => {
    auth.logout()
    router.push("/")
  }

  const isActive = (href: string) => {
    if (href === "/dashboard") return pathname === "/dashboard"
    return pathname.startsWith(href)
  }

  return (
    <div className="flex h-screen bg-background">
      <AnimatePresence>
        {sidebarOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={() => setSidebarOpen(false)}
            className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          />
        )}
      </AnimatePresence>

      <motion.aside
        initial={false}
        animate={{ x: sidebarOpen ? 0 : -300 }}
        className={cn(
          "fixed left-0 top-0 z-50 h-full w-64 shrink-0 border-r bg-card lg:static lg:z-auto lg:translate-x-0",
          "flex flex-col"
        )}
      >
        <div className="flex h-16 items-center gap-2 border-b px-6">
          <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-gold-500">
            <Building2 className="h-5 w-5 text-navy-900" />
          </div>
          <div>
            <p className="text-sm font-bold leading-tight text-foreground">USIB</p>
            <p className="text-[10px] leading-tight text-muted-foreground">United Standard Intl Bank</p>
          </div>
        </div>

        <nav className="flex-1 overflow-y-auto px-3 py-4">
          {sidebarNav.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              onClick={() => setSidebarOpen(false)}
              className={cn(
                "group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors",
                isActive(item.href)
                  ? "bg-usib-50 text-usib-600 dark:bg-usib-800/50 dark:text-usib-200"
                  : "text-muted-foreground hover:bg-muted hover:text-foreground"
              )}
            >
              <item.icon className={cn(
                "h-4.5 w-4.5 shrink-0",
                isActive(item.href) ? "text-gold-500" : ""
              )} />
              <span>{item.label}</span>
              {item.badge && (
                <span className="ml-auto flex h-5 w-5 items-center justify-center rounded-full bg-gold-500 text-[10px] font-bold text-navy-900">
                  {item.badge}
                </span>
              )}
              {isActive(item.href) && (
                <motion.div
                  layoutId="sidebar-active"
                  className="ml-auto h-1.5 w-1.5 rounded-full bg-gold-500"
                />
              )}
            </Link>
          ))}
        </nav>

        <div className="border-t p-4">
          <div className="flex items-center gap-3 rounded-lg bg-gradient-to-r from-gold-500/10 to-gold-500/5 p-3">
            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gold-500">
              <Building2 className="h-4 w-4 text-navy-900" />
            </div>
            <div className="flex-1">
              <p className="text-xs font-medium text-foreground">Premium Banking</p>
              <p className="text-[10px] text-muted-foreground">24/7 Support</p>
            </div>
          </div>
        </div>
      </motion.aside>

      <div className="flex flex-1 flex-col overflow-hidden">
        <header className="flex h-16 shrink-0 items-center gap-4 border-b bg-card px-4 lg:px-6">
          <Button
            variant="ghost"
            size="icon"
            className="lg:hidden"
            onClick={() => setSidebarOpen(!sidebarOpen)}
          >
            {sidebarOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </Button>

          <div className="hidden md:flex md:flex-1">
            <div className="relative max-w-md">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                placeholder="Search accounts, transactions..."
                className="pl-9"
              />
            </div>
          </div>

          <div className="flex items-center gap-2 ml-auto">
            <Button variant="ghost" size="icon" className="relative">
              <Bell className="h-5 w-5" />
              <span className="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-gold-500 text-[9px] font-bold text-navy-900">
                3
              </span>
            </Button>

            <DropdownMenu open={userMenuOpen} onOpenChange={setUserMenuOpen}>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="flex items-center gap-2 pl-2 pr-1">
                  <Avatar>
                    <AvatarFallback className="flex h-8 w-8 items-center justify-center rounded-full bg-usib-500 text-xs font-bold text-white">
                      {initials}
                    </AvatarFallback>
                  </Avatar>
                  <div className="hidden text-left md:block">
                    <p className="text-sm font-medium leading-tight text-foreground">
                      {user ? `${user.firstName} ${user.lastName}` : "User"}
                    </p>
                    <p className="text-[10px] leading-tight text-muted-foreground">Premium Client</p>
                  </div>
                  <ChevronDown className="h-4 w-4 text-muted-foreground" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent
                align="end"
                className="z-50 mt-1 min-w-[200px] overflow-hidden rounded-xl border bg-card p-1 shadow-elevated"
              >
                <DropdownMenuLabel className="px-2 py-1.5 text-xs font-medium text-muted-foreground">
                  {user?.email}
                </DropdownMenuLabel>
                <DropdownMenuSeparator className="my-1 border-t" />
                <DropdownMenuItem asChild>
                  <Link href="/dashboard/profile" className="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-muted">
                    <User className="h-4 w-4" />
                    Profile
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link href="/dashboard/settings" className="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-muted">
                    <Settings className="h-4 w-4" />
                    Settings
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator className="my-1 border-t" />
                <DropdownMenuItem asChild>
                  <button
                    onClick={handleLogout}
                    className="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                  >
                    <LogOut className="h-4 w-4" />
                    Logout
                  </button>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </header>

        <main className="flex-1 overflow-y-auto">
          <div className="mx-auto w-full max-w-7xl p-4 md:p-6 lg:p-8">
            {children}
          </div>
        </main>
      </div>

      <nav className="fixed bottom-0 left-0 right-0 z-30 flex items-center justify-around border-t bg-card px-2 py-2 lg:hidden">
        {bottomNav.map((item) => (
          <Link
            key={item.label}
            href={item.href}
            className={cn(
              "flex flex-col items-center gap-0.5 rounded-lg px-3 py-1 text-[10px] font-medium transition-colors",
              isActive(item.href)
                ? "text-gold-500"
                : "text-muted-foreground"
            )}
          >
            <item.icon className="h-5 w-5" />
            <span>{item.label}</span>
          </Link>
        ))}
      </nav>
    </div>
  )
}
