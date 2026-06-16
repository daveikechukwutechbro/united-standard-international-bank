"use client"

import { useState, useEffect } from "react"
import { useMutation } from "@tanstack/react-query"
import {
  User, Mail, Phone, MapPin, Lock, Shield, Bell,
  CheckCircle2, Eye, EyeOff, Key, Smartphone,
  MessageSquare, ArrowUpDown, CreditCard, Landmark,
  Save, Loader2, RefreshCw
} from "lucide-react"
import { api } from "@/lib/api"
import { auth } from "@/lib/auth"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { useToast } from "@/components/ui/toast"

export default function SettingsPage() {
  const { show, Toast } = useToast()
  const user = auth.getUser()

  const [profile, setProfile] = useState({
    firstName: "",
    lastName: "",
    email: "",
    phone: "",
    street: "",
    city: "",
    state: "",
    zipCode: "",
    country: "",
  })

  const [passwords, setPasswords] = useState({
    currentPassword: "",
    newPassword: "",
    confirmPassword: "",
  })

  const [notifications, setNotifications] = useState({
    transactions: true,
    security: true,
    promotions: false,
    loans: true,
    cards: true,
    system: true,
  })

  useEffect(() => {
    if (user) {
      setProfile({
        firstName: user.firstName,
        lastName: user.lastName,
        email: user.email,
        phone: user.phone,
        street: user.address?.street || "",
        city: user.address?.city || "",
        state: user.address?.state || "",
        zipCode: user.address?.zipCode || "",
        country: user.address?.country || "",
      })
    }
  }, [user])

  const changePasswordMutation = useMutation({
    mutationFn: () => api.changePassword(passwords.currentPassword, passwords.newPassword),
    onSuccess: () => {
      setPasswords({ currentPassword: "", newPassword: "", confirmPassword: "" })
      show("Password changed successfully", "success")
    },
    onError: (err: Error) => {
      show(err.message || "Failed to change password", "error")
    },
  })

  const tfaMutation = useMutation({
    mutationFn: (enable: boolean) => api.toggle2fa(enable),
    onSuccess: (data) => {
      show(data.message, "success")
    },
    onError: (err: Error) => {
      show(err.message || "Failed to update 2FA", "error")
    },
  })

  const handleSaveProfile = (e: React.FormEvent) => {
    e.preventDefault()
    show("Profile updated successfully", "success")
  }

  const handleChangePassword = (e: React.FormEvent) => {
    e.preventDefault()
    if (!passwords.currentPassword || !passwords.newPassword || !passwords.confirmPassword) {
      show("Please fill in all password fields", "error")
      return
    }
    if (passwords.newPassword !== passwords.confirmPassword) {
      show("New passwords do not match", "error")
      return
    }
    if (passwords.newPassword.length < 8) {
      show("Password must be at least 8 characters", "error")
      return
    }
    changePasswordMutation.mutate()
  }

  return (
    <div className="space-y-8">
      {Toast}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold font-display text-foreground">Settings</h1>
          <p className="text-sm text-muted-foreground">Manage your account settings and preferences</p>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-usib-100 text-usib-600">
                <User className="h-5 w-5" />
              </div>
              <div>
                <CardTitle>Personal Information</CardTitle>
                <CardDescription>Update your personal details</CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSaveProfile} className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label>First Name</Label>
                  <Input
                    value={profile.firstName}
                    onChange={(e) => setProfile(prev => ({ ...prev, firstName: e.target.value }))}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Last Name</Label>
                  <Input
                    value={profile.lastName}
                    onChange={(e) => setProfile(prev => ({ ...prev, lastName: e.target.value }))}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Email</Label>
                  <Input
                    type="email"
                    value={profile.email}
                    onChange={(e) => setProfile(prev => ({ ...prev, email: e.target.value }))}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Phone</Label>
                  <Input
                    value={profile.phone}
                    onChange={(e) => setProfile(prev => ({ ...prev, phone: e.target.value }))}
                  />
                </div>
              </div>
              <div className="space-y-2">
                <Label>Address</Label>
                <Input
                  value={profile.street}
                  onChange={(e) => setProfile(prev => ({ ...prev, street: e.target.value }))}
                  placeholder="Street address"
                />
              </div>
              <div className="grid gap-4 md:grid-cols-3">
                <div className="space-y-2">
                  <Label>City</Label>
                  <Input
                    value={profile.city}
                    onChange={(e) => setProfile(prev => ({ ...prev, city: e.target.value }))}
                  />
                </div>
                <div className="space-y-2">
                  <Label>State</Label>
                  <Input
                    value={profile.state}
                    onChange={(e) => setProfile(prev => ({ ...prev, state: e.target.value }))}
                  />
                </div>
                <div className="space-y-2">
                  <Label>ZIP Code</Label>
                  <Input
                    value={profile.zipCode}
                    onChange={(e) => setProfile(prev => ({ ...prev, zipCode: e.target.value }))}
                  />
                </div>
              </div>
              <div className="flex justify-end">
                <Button type="submit">
                  <Save className="mr-2 h-4 w-4" />
                  Save Changes
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 text-red-600">
                <Lock className="h-5 w-5" />
              </div>
              <div>
                <CardTitle>Change Password</CardTitle>
                <CardDescription>Update your password regularly</CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleChangePassword} className="space-y-4">
              <div className="space-y-2">
                <Label>Current Password</Label>
                <Input
                  type="password"
                  value={passwords.currentPassword}
                  onChange={(e) => setPasswords(prev => ({ ...prev, currentPassword: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <Label>New Password</Label>
                <Input
                  type="password"
                  value={passwords.newPassword}
                  onChange={(e) => setPasswords(prev => ({ ...prev, newPassword: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <Label>Confirm New Password</Label>
                <Input
                  type="password"
                  value={passwords.confirmPassword}
                  onChange={(e) => setPasswords(prev => ({ ...prev, confirmPassword: e.target.value }))}
                />
              </div>
              <div className="flex justify-end">
                <Button type="submit" loading={changePasswordMutation.isPending}>
                  {changePasswordMutation.isPending ? "Updating..." : "Update Password"}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-100 text-gold-600">
                <Shield className="h-5 w-5" />
              </div>
              <div>
                <CardTitle>Two-Factor Authentication</CardTitle>
                <CardDescription>Add an extra layer of security to your account</CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center justify-between rounded-lg border p-4">
              <div>
                <p className="text-sm font-medium text-foreground">2FA Status</p>
                <p className="text-xs text-muted-foreground">
                  {user?.twoFactorEnabled ? "Two-factor authentication is active" : "Not enabled"}
                </p>
              </div>
              <Badge variant={user?.twoFactorEnabled ? "success" : "secondary"}>
                {user?.twoFactorEnabled ? "Enabled" : "Disabled"}
              </Badge>
            </div>
            {!user?.twoFactorEnabled && (
              <>
                <div className="flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-6">
                  <Smartphone className="mb-2 h-8 w-8 text-muted-foreground" />
                  <p className="text-sm font-medium text-foreground">QR Code Placeholder</p>
                  <p className="text-xs text-muted-foreground text-center">
                    Scan with Google Authenticator or Authy
                  </p>
                  <div className="mt-3 h-32 w-32 rounded-lg bg-muted flex items-center justify-center">
                    <Shield className="h-10 w-10 text-muted-foreground/50" />
                  </div>
                </div>
                <Button
                  className="w-full"
                  onClick={() => tfaMutation.mutate(true)}
                  loading={tfaMutation.isPending}
                >
                  Enable 2FA
                </Button>
              </>
            )}
            {user?.twoFactorEnabled && (
              <Button
                variant="outline"
                className="w-full"
                onClick={() => tfaMutation.mutate(false)}
                loading={tfaMutation.isPending}
              >
                Disable 2FA
              </Button>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                <Bell className="h-5 w-5" />
              </div>
              <div>
                <CardTitle>Notification Preferences</CardTitle>
                <CardDescription>Choose what notifications you receive</CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            {[
              { key: "transactions", label: "Transactions", description: "Deposits, withdrawals, transfers", icon: ArrowUpDown },
              { key: "security", label: "Security", description: "Login alerts, password changes", icon: Shield },
              { key: "loans", label: "Loans", description: "Payment reminders, loan status", icon: Landmark },
              { key: "cards", label: "Cards", description: "Card transactions, limit alerts", icon: CreditCard },
              { key: "promotions", label: "Promotions", description: "Offers, rates, product updates", icon: MessageSquare },
              { key: "system", label: "System", description: "Scheduled maintenance, updates", icon: Bell },
            ].map((item) => (
              <div key={item.key} className="flex items-center justify-between rounded-lg border p-3">
                <div className="flex items-center gap-3">
                  <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-muted">
                    <item.icon className="h-4 w-4 text-muted-foreground" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-foreground">{item.label}</p>
                    <p className="text-xs text-muted-foreground">{item.description}</p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setNotifications(prev => ({ ...prev, [item.key]: !(prev as any)[item.key] }))}
                  className={cn(
                    "relative h-6 w-11 rounded-full transition-colors",
                    (notifications as any)[item.key] ? "bg-gold-500" : "bg-muted"
                  )}
                >
                  <div className={cn(
                    "absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform",
                    (notifications as any)[item.key] ? "translate-x-[22px]" : "translate-x-0.5"
                  )} />
                </button>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
