"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_USER_PROFILE } from "@/lib/graphql/queries"
import { UPDATE_PROFILE } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { User, Mail, Phone, MapPin, Globe, LogOut } from "lucide-react"
import { useAuth } from "@/hooks/useAuth"

function SettingsContent() {
  const { logout } = useAuth()
  const { data, loading } = useQuery(GET_USER_PROFILE)
  const [updateProfile, { loading: updating }] = useMutation(UPDATE_PROFILE)
  const [editing, setEditing] = useState(false)

  const profile = data?.userProfile

  const [form, setForm] = useState({
    first_name: "",
    last_name: "",
    phone_number: "",
    country: "",
    city: "",
  })

  const startEditing = () => {
    if (profile) {
      setForm({
        first_name: profile.first_name || "",
        last_name: profile.last_name || "",
        phone_number: profile.phone_number || "",
        country: profile.country || "",
        city: profile.city || "",
      })
    }
    setEditing(true)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    await updateProfile({
      variables: {
        input: {
          first_name: form.first_name,
          last_name: form.last_name,
          phone_number: form.phone_number,
          country: form.country,
          city: form.city,
        },
      },
    })
    setEditing(false)
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <div className="h-8 w-8 animate-spin rounded-full border-2 border-blue-500 border-t-transparent" />
      </div>
    )
  }

  const fields = [
    { label: "First Name", value: profile?.first_name, icon: User },
    { label: "Last Name", value: profile?.last_name, icon: User },
    { label: "Email", value: profile?.email, icon: Mail },
    { label: "Phone", value: profile?.phone_number, icon: Phone },
    { label: "Country", value: profile?.country, icon: Globe },
    { label: "City", value: profile?.city, icon: MapPin },
  ]

  return (
    <div className="space-y-6 max-w-2xl">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Settings</h1>
        <p className="text-sm text-neutral-400 mb-6">Manage your profile and preferences</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4 flex items-center justify-between">
          <h2 className="text-sm font-medium text-white">Profile</h2>
          {!editing && (
            <button
              onClick={startEditing}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500"
            >
              Edit Profile
            </button>
          )}
        </div>

        {!editing ? (
          <div className="divide-y divide-neutral-800">
            {fields.map((f) => {
              const Icon = f.icon
              return (
                <div key={f.label} className="flex items-center gap-3 px-5 py-3">
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600/10 text-blue-400">
                    <Icon size={14} />
                  </div>
                  <div>
                    <p className="text-xs text-neutral-400">{f.label}</p>
                    <p className="text-sm text-white">{f.value || "—"}</p>
                  </div>
                </div>
              )
            })}
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="p-5 space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              {[
                { key: "first_name", label: "First Name" },
                { key: "last_name", label: "Last Name" },
                { key: "phone_number", label: "Phone Number" },
                { key: "country", label: "Country" },
                { key: "city", label: "City" },
              ].map((field) => (
                <div key={field.key}>
                  <label className="block text-xs text-neutral-400 mb-1.5">{field.label}</label>
                  <input
                    type="text"
                    value={(form as any)[field.key]}
                    onChange={(e) => setForm({ ...form, [field.key]: e.target.value })}
                    className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  />
                </div>
              ))}
            </div>
            <div className="flex gap-2 pt-2">
              <button
                type="submit"
                disabled={updating}
                className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
              >
                {updating ? "Saving..." : "Save Changes"}
              </button>
              <button
                type="button"
                onClick={() => setEditing(false)}
                className="rounded-lg border border-neutral-700 px-4 py-2 text-sm font-medium text-neutral-300 hover:bg-neutral-800"
              >
                Cancel
              </button>
            </div>
          </form>
        )}
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Account</h2>
        </div>
        <div className="px-5 py-4">
          <button
            onClick={logout}
            className="flex items-center gap-2 rounded-lg bg-red-500/10 px-4 py-2 text-sm font-medium text-red-400 hover:bg-red-500/20"
          >
            <LogOut size={16} />
            Logout
          </button>
        </div>
      </div>
    </div>
  )
}

export default function SettingsPage() {
  return (
    <AppShell>
      <SettingsContent />
    </AppShell>
  )
}
