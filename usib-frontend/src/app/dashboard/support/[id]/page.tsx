"use client"

import { useState } from "react"
import { useParams, useRouter } from "next/navigation"
import { useQuery } from "@tanstack/react-query"
import {
  ArrowLeft, Send, Paperclip, ChevronDown, Clock,
  CheckCircle2, XCircle, AlertCircle, User, Bot,
  FileText, Trash2, Download
} from "lucide-react"
import { api } from "@/lib/api"
import { formatDate, formatCurrency, cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Skeleton } from "@/components/ui/skeleton"
import { useToast } from "@/components/ui/toast"

const statusConfig: Record<string, { label: string; variant: "warning" | "success" | "default" | "danger" | "secondary" }> = {
  open: { label: "Open", variant: "success" },
  in_progress: { label: "In Progress", variant: "default" },
  waiting_on_customer: { label: "Waiting on You", variant: "warning" },
  resolved: { label: "Resolved", variant: "success" },
  closed: { label: "Closed", variant: "secondary" },
}

const priorityConfig: Record<string, { label: string; variant: "warning" | "success" | "default" | "danger" | "secondary" }> = {
  low: { label: "Low", variant: "secondary" },
  normal: { label: "Normal", variant: "default" },
  high: { label: "High", variant: "warning" },
  urgent: { label: "Urgent", variant: "danger" },
}

const categoryLabels: Record<string, string> = {
  account: "Account",
  card: "Card",
  loan: "Loan",
  transfer: "Transfer",
  technical: "Technical",
  fraud: "Fraud",
  other: "Other",
}

export default function TicketDetailPage() {
  const params = useParams()
  const router = useRouter()
  const { show, Toast } = useToast()
  const [replyText, setReplyText] = useState("")

  const { data: ticket, isLoading, error } = useQuery({
    queryKey: ["support-ticket", params.id],
    queryFn: async () => {
      const tickets = await api.getSupportTickets()
      const ticket = tickets.find(t => t.id === params.id)
      if (!ticket) throw new Error("Ticket not found")
      return ticket
    },
  })

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-8 w-64" />
        <div className="grid gap-6 lg:grid-cols-3">
          <div className="lg:col-span-2 space-y-4">
            {[1, 2, 3].map(i => <Skeleton key={i} className="h-24 w-full rounded-xl" />)}
          </div>
          <Skeleton className="h-64 rounded-xl" />
        </div>
      </div>
    )
  }

  if (error || !ticket) {
    return (
      <div className="flex flex-col items-center justify-center py-20">
        <p className="text-lg font-medium text-foreground">Ticket not found</p>
        <p className="text-muted-foreground text-sm">The ticket you are looking for does not exist.</p>
        <Button className="mt-4" onClick={() => router.back()}>Go Back</Button>
      </div>
    )
  }

  const handleSendReply = () => {
    if (!replyText.trim()) return
    show("Reply sent successfully", "success")
    setReplyText("")
  }

  const handleCloseTicket = () => {
    show("Ticket closed", "success")
    router.push("/dashboard/support")
  }

  const statusInfo = statusConfig[ticket.status] || { label: ticket.status, variant: "secondary" }
  const priorityInfo = priorityConfig[ticket.priority] || { label: ticket.priority, variant: "secondary" }

  return (
    <div className="space-y-8">
      {Toast}
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => router.push("/dashboard/support")}>
          <ArrowLeft className="h-5 w-5" />
        </Button>
        <div className="flex-1">
          <div className="flex items-center gap-3">
            <h1 className="text-xl font-bold font-display text-foreground">{ticket.subject}</h1>
            <Badge variant={statusInfo.variant} className="capitalize">{statusInfo.label}</Badge>
            <Badge variant={priorityInfo.variant} className="capitalize">{priorityInfo.label}</Badge>
          </div>
          <p className="text-xs text-muted-foreground mt-0.5">
            Ticket #{ticket.id} • Created {formatDate(ticket.createdAt, "long")}
          </p>
        </div>
        {ticket.status !== "closed" && ticket.status !== "resolved" && (
          <Button variant="destructive" size="sm" onClick={handleCloseTicket}>
            <XCircle className="mr-2 h-4 w-4" />
            Close Ticket
          </Button>
        )}
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Conversation</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {ticket.messages.map((msg) => (
                <div
                  key={msg.id}
                  className={cn(
                    "flex gap-3",
                    msg.senderType === "customer" ? "flex-row" : "flex-row-reverse"
                  )}
                >
                  <div className={cn(
                    "flex h-8 w-8 shrink-0 items-center justify-center rounded-full",
                    msg.senderType === "customer"
                      ? "bg-usib-500 text-white"
                      : "bg-gold-500 text-navy-900"
                  )}>
                    {msg.senderType === "customer" ? (
                      <User className="h-4 w-4" />
                    ) : (
                      <Bot className="h-4 w-4" />
                    )}
                  </div>
                  <div className={cn(
                    "flex max-w-[80%] flex-col rounded-2xl px-4 py-3",
                    msg.senderType === "customer"
                      ? "bg-usib-50 dark:bg-usib-800/50"
                      : "bg-gold-50 dark:bg-gold-900/20"
                  )}>
                    <div className="flex items-center justify-between gap-3 mb-1">
                      <span className="text-xs font-medium text-foreground">{msg.senderName}</span>
                      <span className="text-[10px] text-muted-foreground">{formatDate(msg.createdAt, "relative")}</span>
                    </div>
                    <p className="text-sm text-foreground">{msg.message}</p>
                    {msg.attachments && msg.attachments.length > 0 && (
                      <div className="mt-2 flex items-center gap-2">
                        <Paperclip className="h-3 w-3 text-muted-foreground" />
                        <span className="text-xs text-muted-foreground">{msg.attachments.length} attachment(s)</span>
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>

          {ticket.status !== "closed" && ticket.status !== "resolved" && (
            <Card>
              <CardContent className="p-4">
                <div className="space-y-3">
                  <Textarea
                    placeholder="Type your reply..."
                    rows={3}
                    value={replyText}
                    onChange={(e) => setReplyText(e.target.value)}
                  />
                  <div className="flex items-center justify-between">
                    <Button variant="outline" size="sm">
                      <Paperclip className="mr-2 h-4 w-4" />
                      Attach File
                    </Button>
                    <Button onClick={handleSendReply} disabled={!replyText.trim()}>
                      <Send className="mr-2 h-4 w-4" />
                      Send Reply
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          )}
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Ticket Info</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Status</span>
                <Badge variant={statusInfo.variant} className="capitalize">{statusInfo.label}</Badge>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Priority</span>
                <Badge variant={priorityInfo.variant} className="capitalize">{priorityInfo.label}</Badge>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Category</span>
                <span className="font-medium text-foreground">{categoryLabels[ticket.category] || ticket.category}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Created</span>
                <span className="font-medium text-foreground">{formatDate(ticket.createdAt)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Last Updated</span>
                <span className="font-medium text-foreground">{formatDate(ticket.updatedAt, "relative")}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Messages</span>
                <span className="font-medium text-foreground">{ticket.messages.length}</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Need Help?</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
              <p className="text-muted-foreground">
                For urgent matters, please call our 24/7 support line.
              </p>
              <div className="rounded-lg bg-muted p-3 text-center">
                <p className="text-lg font-bold text-foreground">+1 (555) 000-1234</p>
                <p className="text-xs text-muted-foreground">Available 24/7</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  )
}
