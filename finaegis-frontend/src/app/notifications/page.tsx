"use client"

import { AppShell } from "@/components/layout/AppShell"
import { Bell, Mail, MessageSquare, AlertTriangle, Info } from "lucide-react"

const categories = [
  {
    title: "Transaction Alerts",
    icon: Bell,
    description: "Payment confirmations, transfers, and balance changes",
    channels: ["Email", "Push", "SMS"],
  },
  {
    title: "Security Notifications",
    icon: AlertTriangle,
    description: "Login attempts, device changes, and suspicious activity",
    channels: ["Email", "Push"],
  },
  {
    title: "Marketing & Updates",
    icon: Mail,
    description: "Product updates, promotions, and new features",
    channels: ["Email"],
  },
  {
    title: "Compliance & Legal",
    icon: Info,
    description: "KYC status, regulatory notices, and policy changes",
    channels: ["Email", "Push"],
  },
]

const channels = [
  { name: "Email", icon: Mail, defaultEnabled: true },
  { name: "Push", icon: Bell, defaultEnabled: true },
  { name: "SMS", icon: MessageSquare, defaultEnabled: false },
]

function NotificationsContent() {
  return (
    <div className="space-y-6 max-w-2xl">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Notifications</h1>
        <p className="text-sm text-neutral-400 mb-6">Manage your notification preferences</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Notification Categories</h2>
        </div>
        <div className="divide-y divide-neutral-800">
          {categories.map((cat) => {
            const Icon = cat.icon
            return (
              <div key={cat.title} className="px-5 py-4">
                <div className="flex items-start gap-3">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400 shrink-0">
                    <Icon size={14} />
                  </div>
                  <div className="flex-1">
                    <p className="text-sm font-medium text-white">{cat.title}</p>
                    <p className="text-xs text-neutral-400 mt-0.5">{cat.description}</p>
                    <div className="flex gap-2 mt-2">
                      {cat.channels.map((ch) => (
                        <span
                          key={ch}
                          className="inline-flex items-center rounded-full bg-neutral-800 px-2 py-0.5 text-xs font-medium text-neutral-300"
                        >
                          {ch}
                        </span>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            )
          })}
        </div>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Channel Preferences</h2>
        </div>
        <div className="divide-y divide-neutral-800">
          {channels.map((ch) => {
            const Icon = ch.icon
            return (
              <div key={ch.name} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                    <Icon size={14} />
                  </div>
                  <p className="text-sm text-white">{ch.name}</p>
                </div>
                <label className="relative inline-flex cursor-pointer items-center">
                  <input
                    type="checkbox"
                    defaultChecked={ch.defaultEnabled}
                    className="peer sr-only"
                  />
                  <div className="h-5 w-9 rounded-full bg-neutral-700 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-blue-600 peer-checked:after:translate-x-full" />
                </label>
              </div>
            )
          })}
        </div>
      </div>

      <p className="text-xs text-neutral-500 text-center">
        Notification preferences are managed locally. Server-side sync coming soon.
      </p>
    </div>
  )
}

export default function NotificationsPage() {
  return (
    <AppShell>
      <NotificationsContent />
    </AppShell>
  )
}
