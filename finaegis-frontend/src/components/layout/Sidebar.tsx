"use client"

import Link from "next/link"
import { usePathname } from "next/navigation"
import { cn } from "@/lib/utils"
import {
  LayoutDashboard, Building2, ArrowLeftRight, Wallet, TrendingUp,
  HandCoins, ShieldCheck, Coins, PieChart, BarChart3, Bot,
  Settings, Menu, X, CreditCard, Globe, Workflow, GanttChart,
  Scale, Users, Store, Package, FileText, Building,
  Smartphone, DollarSign, Vote, Award, Webhook, Shield,
  Cpu, BrainCircuit, BadgeDollarSign, ChevronDown, ChevronRight,
  Landmark
} from "lucide-react"
import { useState } from "react"

interface NavItem {
  label: string
  href?: string
  icon: React.ReactNode
  children?: NavItem[]
}

const navigation: NavItem[] = [
  { label: "Dashboard", href: "/dashboard", icon: <LayoutDashboard size={18} /> },
  { label: "Accounts", href: "/accounts", icon: <Building2 size={18} /> },
  { label: "Transfers", href: "/transfers", icon: <ArrowLeftRight size={18} /> },
  { label: "Exchange", href: "/exchange", icon: <BarChart3 size={18} /> },
  { label: "Wallets", href: "/wallet", icon: <Wallet size={18} /> },
  { label: "Lending", href: "/lending", icon: <HandCoins size={18} /> },
  { label: "Stablecoins", href: "/stablecoin", icon: <Coins size={18} /> },
  { label: "Treasury", href: "/treasury", icon: <PieChart size={18} /> },
  { label: "GCU Basket", href: "/basket", icon: <Globe size={18} /> },
  { label: "Cards", href: "/cards", icon: <CreditCard size={18} /> },
  { label: "Compliance", href: "/compliance", icon: <ShieldCheck size={18} /> },
  { label: "Fraud", href: "/compliance", icon: <Shield size={18} /> },
  {
    label: "Crypto", icon: <TrendingUp size={18} />, children: [
      { label: "Cross-Chain", href: "/crosschain", icon: <Workflow size={16} /> },
      { label: "DeFi", href: "/defi", icon: <GanttChart size={16} /> },
      { label: "Smart Accounts", href: "/wallet", icon: <Cpu size={16} /> },
    ]
  },
  { label: "x402 Payments", href: "/x402", icon: <BadgeDollarSign size={18} /> },
  { label: "AI Agents", href: "/ai", icon: <Bot size={18} /> },
  { label: "Agent Protocol", href: "/ai", icon: <BrainCircuit size={18} /> },
  { label: "Ledger", href: "/accounts", icon: <Scale size={18} /> },
  { label: "Investments (CGO)", href: "/cgo", icon: <DollarSign size={18} /> },
  { label: "Governance", href: "/governance", icon: <Vote size={18} /> },
  { label: "Rewards", href: "/rewards", icon: <Award size={18} /> },
  { label: "Merchants", href: "/merchants", icon: <Store size={18} /> },
  { label: "Products", href: "/products", icon: <Package size={18} /> },
  { label: "Custodians", href: "/custodian", icon: <Landmark size={18} /> },
  { label: "Regulatory", href: "/regulatory", icon: <FileText size={18} /> },
  { label: "Partners (FI)", href: "/partners", icon: <Building size={18} /> },
  { label: "Interledger", href: "/interledger", icon: <Globe size={18} /> },
  { label: "Microfinance", href: "/microfinance", icon: <Users size={18} /> },
  { label: "Open Banking", href: "/open-banking", icon: <Building2 size={18} /> },
  { label: "Mobile Devices", href: "/mobile", icon: <Smartphone size={18} /> },
  { label: "Webhooks", href: "/webhooks", icon: <Webhook size={18} /> },
  { label: "Batch Jobs", href: "/batch", icon: <Workflow size={18} /> },
  { label: "Subscriptions", href: "/subscriptions", icon: <BadgeDollarSign size={18} /> },
  { label: "Developers", href: "/developers", icon: <Settings size={18} /> },
  { label: "Admin", href: "/admin", icon: <Shield size={18} /> },
  { label: "Settings", href: "/settings", icon: <Settings size={18} /> },
]

function NavItemComponent({ item, depth = 0, onNavigate }: { item: NavItem; depth?: number; onNavigate?: () => void }) {
  const pathname = usePathname()
  const [open, setOpen] = useState(false)
  const isActive = item.href ? pathname === item.href || pathname.startsWith(item.href + "/") : false

  if (item.children && item.children.length > 0) {
    return (
      <div>
        <button
          onClick={() => setOpen(!open)}
          className={cn(
            "flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm transition-colors",
            "text-neutral-400 hover:bg-neutral-800 hover:text-white"
          )}
        >
          {item.icon}
          <span className="flex-1 text-left">{item.label}</span>
          {open ? <ChevronDown size={14} /> : <ChevronRight size={14} />}
        </button>
        {open && (
          <div className="ml-4 mt-1 space-y-1">
            {item.children.map((child, i) => (
              <NavItemComponent key={i} item={child} depth={depth + 1} onNavigate={onNavigate} />
            ))}
          </div>
        )}
      </div>
    )
  }

  if (!item.href) return null

  return (
    <Link
      href={item.href}
      onClick={onNavigate}
      className={cn(
        "flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition-colors",
        isActive
          ? "bg-neutral-800 text-white font-medium"
          : "text-neutral-400 hover:bg-neutral-800 hover:text-white"
      )}
    >
      {item.icon}
      <span>{item.label}</span>
    </Link>
  )
}

export function Sidebar({ onNavigate }: { onNavigate?: () => void }) {
  return (
    <nav className="flex h-full flex-col gap-1 overflow-y-auto p-3">
      <div className="mb-4 px-3">
        <Link href="/dashboard" className="flex items-center gap-2" onClick={onNavigate}>
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white">
            FA
          </div>
          <span className="text-lg font-semibold text-white">FinAegis</span>
        </Link>
      </div>
      {navigation.map((item, i) => (
        <NavItemComponent key={i} item={item} onNavigate={onNavigate} />
      ))}
    </nav>
  )
}
