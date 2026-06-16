"use client"

import { AppShell } from "@/components/layout/AppShell"
import { Users, Puzzle, Activity, Shield, BarChart3, Settings } from "lucide-react"

const stats = [
  {
    title: "Total Users",
    value: "—",
    subtitle: "Active accounts on the platform",
    icon: Users,
  },
  {
    title: "Active Plugins",
    value: "—",
    subtitle: "Currently enabled extensions",
    icon: Puzzle,
  },
  {
    title: "System Health",
    value: "—",
    subtitle: "Overall platform status",
    icon: Activity,
  },
]

const adminLinks = [
  {
    title: "User Management",
    description: "View, edit, and manage user accounts and permissions",
    icon: Users,
    href: "#",
  },
  {
    title: "Plugin Marketplace",
    description: "Browse, install, and manage platform plugins",
    icon: Puzzle,
    href: "#",
  },
  {
    title: "System Health",
    description: "Monitor system performance, uptime, and logs",
    icon: Activity,
    href: "#",
  },
  {
    title: "Security",
    description: "Configure security policies, audits, and access control",
    icon: Shield,
    href: "#",
  },
  {
    title: "Analytics",
    description: "Platform-wide usage metrics and reports",
    icon: BarChart3,
    href: "#",
  },
  {
    title: "Configuration",
    description: "Global platform settings and feature flags",
    icon: Settings,
    href: "#",
  },
]

function AdminContent() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Admin</h1>
        <p className="text-sm text-neutral-400 mb-6">Platform administration and management</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {stats.map((s) => {
          const Icon = s.icon
          return (
            <div key={s.title} className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-sm text-neutral-400">{s.title}</p>
                  <p className="mt-1 text-2xl font-semibold text-white">{s.value}</p>
                  <p className="mt-1 text-xs text-neutral-500">{s.subtitle}</p>
                </div>
                <div className="rounded-lg bg-blue-600/10 p-2.5 text-blue-400">
                  <Icon size={20} />
                </div>
              </div>
            </div>
          )
        })}
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {adminLinks.map((link) => {
          const Icon = link.icon
          return (
            <a
              key={link.title}
              href={link.href}
              className="rounded-xl border border-neutral-800 bg-neutral-900 p-5 hover:border-neutral-700 transition-colors"
            >
              <div className="flex items-center gap-3 mb-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400">
                  <Icon size={20} />
                </div>
                <h3 className="text-sm font-medium text-white">{link.title}</h3>
              </div>
              <p className="text-xs text-neutral-500">{link.description}</p>
            </a>
          )
        })}
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
        <div className="flex items-center gap-3 mb-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/10 text-blue-400">
            <Activity size={20} />
          </div>
          <div>
            <h2 className="text-sm font-medium text-white">Recent Activity</h2>
            <p className="text-xs text-neutral-400">Latest platform events</p>
          </div>
        </div>
        <div className="rounded-lg bg-neutral-800 px-4 py-3 text-center text-sm text-neutral-500">
          Activity feed will appear here once data is available
        </div>
      </div>
    </div>
  )
}

export default function AdminPage() {
  return (
    <AppShell>
      <AdminContent />
    </AppShell>
  )
}
