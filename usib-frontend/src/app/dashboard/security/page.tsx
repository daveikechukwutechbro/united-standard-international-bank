"use client"

import { useState } from "react"
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query"
import {
  Shield, Smartphone, Laptop, Monitor, AlertTriangle,
  CheckCircle2, XCircle, Clock, Globe, Trash2, LogOut,
  Key, Fingerprint, RefreshCw, ChevronRight
} from "lucide-react"
import { api } from "@/lib/api"
import { formatDate, formatCurrency, cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter
} from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Skeleton } from "@/components/ui/skeleton"
import {
  Dialog, DialogContent, DialogHeader, DialogFooter, DialogTitle, DialogDescription, DialogClose
} from "@/components/ui/dialog"
import { useToast } from "@/components/ui/toast"
import { auth } from "@/lib/auth"

const deviceIcons: Record<string, React.ElementType> = {
  desktop: Monitor,
  mobile: Smartphone,
  laptop: Laptop,
  tablet: Smartphone,
}

const loginHistory = [
  { id: "LH-001", date: "2024-12-01T09:30:00Z", device: "Chrome on Windows", location: "New York, US", ip: "192.168.1.100", status: "success" },
  { id: "LH-002", date: "2024-11-30T22:15:00Z", device: "Safari on iPhone", location: "New York, US", ip: "192.168.1.101", status: "success" },
  { id: "LH-003", date: "2024-11-28T14:00:00Z", device: "Firefox on MacBook", location: "Chicago, US", ip: "203.0.113.45", status: "success" },
  { id: "LH-004", date: "2024-11-25T08:30:00Z", device: "Chrome on Android", location: "Unknown", ip: "198.51.100.22", status: "failed" },
]

export default function SecurityPage() {
  const queryClient = useQueryClient()
  const { show, Toast } = useToast()
  const [revokeConfirmId, setRevokeConfirmId] = useState<string | null>(null)
  const user = auth.getUser()

  const { data: sessions, isLoading } = useQuery({
    queryKey: ["sessions"],
    queryFn: api.getSessions,
  })

  const revokeMutation = useMutation({
    mutationFn: api.revokeSession,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["sessions"] })
      setRevokeConfirmId(null)
      show("Session revoked successfully", "success")
    },
    onError: (err: Error) => {
      show(err.message || "Failed to revoke session", "error")
    },
  })

  return (
    <div className="space-y-8">
      {Toast}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold font-display text-foreground">Security</h1>
          <p className="text-sm text-muted-foreground">Manage your account security and active sessions</p>
        </div>
      </div>

      <div className="grid gap-6 md:grid-cols-3">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 text-green-600">
                <Shield className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground">2FA Status</p>
                <div className="flex items-center gap-2">
                  <p className="text-lg font-bold text-foreground">
                    {user?.twoFactorEnabled ? "Enabled" : "Disabled"}
                  </p>
                  <Badge variant={user?.twoFactorEnabled ? "success" : "secondary"}>
                    {user?.twoFactorEnabled ? "Active" : "Inactive"}
                  </Badge>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                <Key className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground">KYC Status</p>
                <div className="flex items-center gap-2">
                  <p className="text-lg font-bold text-foreground capitalize">{user?.kycStatus?.replace("_", " ") || "Not Submitted"}</p>
                  <Badge variant={user?.kycStatus === "approved" ? "success" : "warning"} className="capitalize">
                    {user?.kycStatus?.replace("_", " ") || "N/A"}
                  </Badge>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-100 text-gold-600">
                <Clock className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Last Password Change</p>
                <p className="text-lg font-bold text-foreground">{user?.updatedAt ? formatDate(user.updatedAt) : "N/A"}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Active Sessions</CardTitle>
          <CardDescription>Manage devices currently logged into your account</CardDescription>
        </CardHeader>
        <CardContent className="p-0">
          {isLoading ? (
            <div className="p-6 space-y-3">
              {[1, 2, 3].map(i => <Skeleton key={i} className="h-16 w-full rounded-lg" />)}
            </div>
          ) : sessions && sessions.length > 0 ? (
            <div className="divide-y">
              {sessions.map((session) => {
                const DeviceIcon = deviceIcons[session.deviceType] || Monitor
                return (
                  <div key={session.id} className="flex items-center gap-4 px-6 py-4">
                    <div className={cn(
                      "flex h-10 w-10 items-center justify-center rounded-lg",
                      session.isCurrent ? "bg-green-100 text-green-600" : "bg-muted text-muted-foreground"
                    )}>
                      <DeviceIcon className="h-5 w-5" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2">
                        <p className="text-sm font-medium text-foreground">{session.deviceName}</p>
                        {session.isCurrent && (
                          <Badge variant="success" className="text-[10px]">Current</Badge>
                        )}
                      </div>
                      <p className="text-xs text-muted-foreground">
                        {session.browser} • {session.location} • {session.ipAddress}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        Last active {formatDate(session.lastActive, "relative")}
                      </p>
                    </div>
                    {!session.isCurrent && (
                      <Button
                        variant="ghost"
                        size="sm"
                        className="text-red-500 hover:text-red-700 hover:bg-red-50"
                        onClick={() => setRevokeConfirmId(session.id)}
                      >
                        <LogOut className="h-4 w-4" />
                      </Button>
                    )}
                  </div>
                )
              })}
            </div>
          ) : (
            <div className="flex flex-col items-center py-12 text-center">
              <Monitor className="mb-2 h-8 w-8 text-muted-foreground" />
              <p className="text-sm font-medium text-foreground">No active sessions</p>
            </div>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Login History</CardTitle>
          <CardDescription>Recent login activity on your account</CardDescription>
        </CardHeader>
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-xs font-medium text-muted-foreground">
                  <th className="px-6 py-3">Date & Time</th>
                  <th className="px-6 py-3">Device</th>
                  <th className="px-6 py-3">Location</th>
                  <th className="px-6 py-3">IP Address</th>
                  <th className="px-6 py-3">Status</th>
                </tr>
              </thead>
              <tbody>
                {loginHistory.map((entry) => (
                  <tr key={entry.id} className="border-b last:border-0 hover:bg-muted/50">
                    <td className="px-6 py-4 text-foreground">{formatDate(entry.date)}</td>
                    <td className="px-6 py-4 text-muted-foreground">{entry.device}</td>
                    <td className="px-6 py-4 text-muted-foreground">{entry.location}</td>
                    <td className="px-6 py-4 text-muted-foreground font-mono text-xs">{entry.ip}</td>
                    <td className="px-6 py-4">
                      <Badge variant={entry.status === "success" ? "success" : "danger"}>
                        {entry.status}
                      </Badge>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Security Recommendations</CardTitle>
          <CardDescription>Improve your account security</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-start gap-3 rounded-lg border p-4">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
              <AlertTriangle className="h-4 w-4" />
            </div>
            <div>
              <p className="text-sm font-medium text-foreground">
                {user?.twoFactorEnabled ? "2FA is enabled" : "Enable Two-Factor Authentication"}
              </p>
              <p className="text-xs text-muted-foreground">
                {user?.twoFactorEnabled
                  ? "Your account is protected with 2FA. Make sure your recovery codes are stored safely."
                  : "Add an extra layer of security to prevent unauthorized access."}
              </p>
            </div>
          </div>
          <div className="flex items-start gap-3 rounded-lg border p-4">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
              <Key className="h-4 w-4" />
            </div>
            <div>
              <p className="text-sm font-medium text-foreground">Use Strong Passwords</p>
              <p className="text-xs text-muted-foreground">
                Use a combination of letters, numbers, and symbols. Avoid reusing passwords across services.
              </p>
            </div>
          </div>
          <div className="flex items-start gap-3 rounded-lg border p-4">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600">
              <RefreshCw className="h-4 w-4" />
            </div>
            <div>
              <p className="text-sm font-medium text-foreground">Regular Password Updates</p>
              <p className="text-xs text-muted-foreground">
                Change your password every 3 months and avoid using obvious personal information.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Dialog open={!!revokeConfirmId} onOpenChange={(o) => !o && setRevokeConfirmId(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Revoke Session</DialogTitle>
            <DialogDescription>This will log out the device from your account.</DialogDescription>
          </DialogHeader>
          <p className="text-sm text-muted-foreground">
            Are you sure you want to revoke this session? The device will be signed out immediately.
          </p>
          <DialogFooter>
            <DialogClose asChild>
              <Button variant="outline">Cancel</Button>
            </DialogClose>
            <Button
              variant="destructive"
              onClick={() => revokeConfirmId && revokeMutation.mutate(revokeConfirmId)}
              loading={revokeMutation.isPending}
            >
              Revoke Session
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
