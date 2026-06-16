"use client"

import { useState } from "react"
import { Sidebar } from "./Sidebar"
import { Header } from "./Header"
import { cn } from "@/lib/utils"

export function AppShell({ children }: { children: React.ReactNode }) {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const mockUser = { email: "demo@finaegis.org", id: 1 }

  return (
    <div className="flex h-screen bg-neutral-950 text-white">
      <aside className="fixed inset-y-0 left-0 z-50 w-60 -translate-x-full border-r border-neutral-800 bg-neutral-950 transition-transform lg:static lg:translate-x-0">
        <Sidebar onNavigate={() => setSidebarOpen(false)} />
      </aside>

      {sidebarOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      <aside
        className={cn(
          "fixed inset-y-0 left-0 z-50 w-60 -translate-x-full border-r border-neutral-800 bg-neutral-950 transition-transform lg:hidden",
          sidebarOpen && "translate-x-0"
        )}
      >
        <Sidebar onNavigate={() => setSidebarOpen(false)} />
      </aside>

      <div className="flex flex-1 flex-col overflow-hidden">
        <Header user={mockUser} onLogout={() => {}} onMenuToggle={() => setSidebarOpen(true)} />
        <main className="flex-1 overflow-y-auto p-4 lg:p-6">
          {children}
        </main>
      </div>
    </div>
  )
}
