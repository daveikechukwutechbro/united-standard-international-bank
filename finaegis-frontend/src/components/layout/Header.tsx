"use client"

import { useState } from "react"
import { Bell, LogOut, Menu, User, X, ChevronDown } from "lucide-react"
import { cn } from "@/lib/utils"
import Link from "next/link"

interface HeaderProps {
  user: any
  onLogout: () => void
  onMenuToggle: () => void
}

export function Header({ user, onLogout, onMenuToggle }: HeaderProps) {
  const [profileOpen, setProfileOpen] = useState(false)

  return (
    <header className="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-neutral-800 bg-neutral-950 px-4">
      <button
        onClick={onMenuToggle}
        className="rounded-lg p-2 text-neutral-400 hover:bg-neutral-800 hover:text-white lg:hidden"
      >
        <Menu size={20} />
      </button>

      <div className="hidden lg:block" />

      <div className="flex items-center gap-3">
        <button className="relative rounded-lg p-2 text-neutral-400 hover:bg-neutral-800 hover:text-white">
          <Bell size={18} />
          <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-blue-500" />
        </button>

        <div className="relative">
          <button
            onClick={() => setProfileOpen(!profileOpen)}
            className="flex items-center gap-2 rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-800 hover:text-white"
          >
            <div className="flex h-7 w-7 items-center justify-center rounded-full bg-blue-600 text-xs font-medium text-white">
              {user?.email?.charAt(0).toUpperCase() || "U"}
            </div>
            <span className="hidden text-sm text-neutral-300 sm:block">
              {user?.email || "User"}
            </span>
            <ChevronDown size={14} />
          </button>

          {profileOpen && (
            <>
              <div className="fixed inset-0 z-40" onClick={() => setProfileOpen(false)} />
              <div className="absolute right-0 top-full z-50 mt-1 w-56 rounded-lg border border-neutral-800 bg-neutral-900 p-1 shadow-xl">
                <Link
                  href="/settings"
                  onClick={() => setProfileOpen(false)}
                  className="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-neutral-300 hover:bg-neutral-800"
                >
                  <User size={16} />
                  Profile
                </Link>
                <button
                  onClick={() => { setProfileOpen(false); onLogout() }}
                  className="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-red-400 hover:bg-neutral-800"
                >
                  <LogOut size={16} />
                  Logout
                </button>
              </div>
            </>
          )}
        </div>
      </div>
    </header>
  )
}
