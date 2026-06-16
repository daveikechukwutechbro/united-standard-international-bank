"use client"

import { useState } from "react"
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query"
import {
  Bell, BellOff, CreditCard, Shield, ArrowUpDown, Landmark,
  Settings, AlertCircle, CheckCircle2, Circle, Clock,
  ExternalLink, Trash2, Filter
} from "lucide-react"
import { api } from "@/lib/api"
import { formatDate, cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Card, CardHeader, CardTitle, CardDescription, CardContent
} from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Skeleton } from "@/components/ui/skeleton"
import {
  Select, SelectGroup, SelectValue, SelectTrigger, SelectContent, SelectItem
} from "@/components/ui/select"
import { useToast } from "@/components/ui/toast"

const typeIcons: Record<string, React.ElementType> = {
  transaction: ArrowUpDown,
  security: Shield,
  card: CreditCard,
  loan: Landmark,
  system: Settings,
  kyc: CheckCircle2,
  promotion: Bell,
}

const typeColors: Record<string, string> = {
  transaction: "bg-blue-100 text-blue-600",
  security: "bg-red-100 text-red-600",
  card: "bg-purple-100 text-purple-600",
  loan: "bg-amber-100 text-amber-600",
  system: "bg-gray-100 text-gray-600",
  kyc: "bg-green-100 text-green-600",
  promotion: "bg-pink-100 text-pink-600",
}

const filterTabs = [
  { key: "all", label: "All" },
  { key: "transaction", label: "Transactions" },
  { key: "security", label: "Security" },
  { key: "card", label: "Cards" },
  { key: "loan", label: "Loans" },
  { key: "system", label: "System" },
]

export default function NotificationsPage() {
  const queryClient = useQueryClient()
  const { show, Toast } = useToast()
  const [activeFilter, setActiveFilter] = useState("all")

  const { data: notifications, isLoading } = useQuery({
    queryKey: ["notifications"],
    queryFn: api.getNotifications,
  })

  const markReadMutation = useMutation({
    mutationFn: api.markNotificationRead,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["notifications"] })
    },
  })

  const filtered = notifications?.filter(n =>
    activeFilter === "all" || n.type === activeFilter
  ) || []

  const unreadCount = notifications?.filter(n => !n.isRead).length || 0

  return (
    <div className="space-y-8">
      {Toast}
      <div className="flex items-center justify-between">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold font-display text-foreground">Notifications</h1>
            {unreadCount > 0 && (
              <Badge variant="default" className="bg-gold-500 text-navy-900">
                {unreadCount} new
              </Badge>
            )}
          </div>
          <p className="text-sm text-muted-foreground">Stay updated with your account activity</p>
        </div>
        <Button variant="outline" size="sm">
          <CheckCircle2 className="mr-2 h-4 w-4" />
          Mark all as read
        </Button>
      </div>

      <div className="flex gap-2 overflow-x-auto pb-2">
        {filterTabs.map((tab) => (
          <button
            key={tab.key}
            onClick={() => setActiveFilter(tab.key)}
            className={cn(
              "whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium transition-colors",
              activeFilter === tab.key
                ? "bg-usib-500 text-white"
                : "bg-muted text-muted-foreground hover:bg-muted/80"
            )}
          >
            {tab.label}
          </button>
        ))}
      </div>

      <Card>
        <CardContent className="p-0">
          {isLoading ? (
            <div className="space-y-2 p-6">
              {[1, 2, 3, 4, 5].map(i => (
                <Skeleton key={i} className="h-20 w-full rounded-lg" />
              ))}
            </div>
          ) : filtered.length > 0 ? (
            <div className="divide-y">
              {filtered.map((notification) => {
                const Icon = typeIcons[notification.type] || Bell
                const colorClass = typeColors[notification.type] || "bg-gray-100 text-gray-600"
                return (
                  <div
                    key={notification.id}
                    className={cn(
                      "flex items-start gap-4 px-6 py-4 transition-colors hover:bg-muted/50 cursor-pointer",
                      !notification.isRead && "bg-usib-50/50 dark:bg-usib-800/20"
                    )}
                    onClick={() => {
                      if (!notification.isRead) {
                        markReadMutation.mutate(notification.id)
                      }
                    }}
                  >
                    <div className="relative">
                      <div className={cn("flex h-10 w-10 items-center justify-center rounded-lg", colorClass)}>
                        <Icon className="h-5 w-5" />
                      </div>
                      {!notification.isRead && (
                        <div className="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-gold-500 border-2 border-background" />
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-start justify-between gap-2">
                        <p className={cn(
                          "text-sm",
                          !notification.isRead ? "font-semibold text-foreground" : "text-foreground"
                        )}>
                          {notification.title}
                        </p>
                        <span className="shrink-0 text-xs text-muted-foreground">
                          {formatDate(notification.createdAt, "relative")}
                        </span>
                      </div>
                      <p className="mt-0.5 text-sm text-muted-foreground line-clamp-2">
                        {notification.message}
                      </p>
                      <div className="mt-2 flex items-center gap-2">
                        <Badge variant="secondary" className="text-[10px] capitalize">
                          {notification.type}
                        </Badge>
                        {!notification.isRead && (
                          <span className="text-[10px] font-medium text-gold-500">New</span>
                        )}
                      </div>
                    </div>
                  </div>
                )
              })}
            </div>
          ) : (
            <div className="flex flex-col items-center py-16 text-center">
              <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                <BellOff className="h-8 w-8 text-muted-foreground" />
              </div>
              <p className="text-lg font-medium text-foreground">No notifications</p>
              <p className="text-sm text-muted-foreground">
                {activeFilter === "all"
                  ? "You're all caught up! New notifications will appear here."
                  : `No ${activeFilter} notifications found.`}
              </p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
