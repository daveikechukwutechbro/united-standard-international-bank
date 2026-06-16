"use client"

import { useState } from "react"
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query"
import { useRouter } from "next/navigation"
import {
  Headphones, Plus, MessageSquare, ChevronRight, Clock,
  CheckCircle2, AlertCircle, ArrowUpRight, FileText,
  AlertTriangle, Info, HelpCircle
} from "lucide-react"
import { api } from "@/lib/api"
import { formatDate, cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Skeleton } from "@/components/ui/skeleton"
import {
  Select, SelectGroup, SelectValue, SelectTrigger, SelectContent, SelectItem
} from "@/components/ui/select"
import {
  Dialog, DialogContent, DialogHeader, DialogFooter, DialogTitle, DialogDescription, DialogClose
} from "@/components/ui/dialog"
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

const categories = [
  { value: "account", label: "Account" },
  { value: "card", label: "Card" },
  { value: "loan", label: "Loan" },
  { value: "transfer", label: "Transfer" },
  { value: "technical", label: "Technical" },
  { value: "fraud", label: "Fraud" },
  { value: "other", label: "Other" },
]

export default function SupportPage() {
  const router = useRouter()
  const queryClient = useQueryClient()
  const { show, Toast } = useToast()
  const [createOpen, setCreateOpen] = useState(false)
  const [formData, setFormData] = useState({ subject: "", category: "", message: "" })

  const { data: tickets, isLoading } = useQuery({
    queryKey: ["support-tickets"],
    queryFn: api.getSupportTickets,
  })

  const createMutation = useMutation({
    mutationFn: () => api.createSupportTicket({
      subject: formData.subject,
      category: formData.category,
      message: formData.message,
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["support-tickets"] })
      setCreateOpen(false)
      setFormData({ subject: "", category: "", message: "" })
      show("Ticket created successfully", "success")
    },
    onError: (err: Error) => {
      show(err.message || "Failed to create ticket", "error")
    },
  })

  const handleCreate = (e: React.FormEvent) => {
    e.preventDefault()
    if (!formData.subject || !formData.category || !formData.message) {
      show("Please fill in all fields", "error")
      return
    }
    createMutation.mutate()
  }

  return (
    <div className="space-y-8">
      {Toast}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold font-display text-foreground">Support</h1>
          <p className="text-sm text-muted-foreground">Get help and manage your support tickets</p>
        </div>
        <Button onClick={() => setCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          New Ticket
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>My Tickets</CardTitle>
          <CardDescription>Your support requests and their status</CardDescription>
        </CardHeader>
        <CardContent className="p-0">
          {isLoading ? (
            <div className="p-6 space-y-3">
              {[1, 2, 3].map(i => <Skeleton key={i} className="h-24 w-full rounded-xl" />)}
            </div>
          ) : tickets && tickets.length > 0 ? (
            <div className="divide-y">
              {tickets.map((ticket) => {
                const lastMsg = ticket.messages[ticket.messages.length - 1]
                const statusInfo = statusConfig[ticket.status] || { label: ticket.status, variant: "secondary" }
                const priorityInfo = priorityConfig[ticket.priority] || { label: ticket.priority, variant: "secondary" }
                return (
                  <div
                    key={ticket.id}
                    className="flex items-start gap-4 px-6 py-5 hover:bg-muted/50 cursor-pointer transition-colors"
                    onClick={() => router.push(`/dashboard/support/${ticket.id}`)}
                  >
                    <div className={cn(
                      "flex h-10 w-10 shrink-0 items-center justify-center rounded-lg",
                      ticket.status === "open" ? "bg-green-100 text-green-600" :
                      ticket.status === "in_progress" ? "bg-blue-100 text-blue-600" :
                      ticket.status === "waiting_on_customer" ? "bg-amber-100 text-amber-600" :
                      "bg-muted text-muted-foreground"
                    )}>
                      <MessageSquare className="h-5 w-5" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-start justify-between gap-2">
                        <div>
                          <h3 className="font-semibold text-foreground">{ticket.subject}</h3>
                          <p className="text-xs text-muted-foreground mt-0.5 capitalize">
                            {ticket.category} • Created {formatDate(ticket.createdAt)}
                          </p>
                        </div>
                        <div className="flex items-center gap-2 shrink-0">
                          <Badge variant={priorityInfo.variant} className="capitalize text-[10px]">
                            {priorityInfo.label}
                          </Badge>
                          <Badge variant={statusInfo.variant} className="capitalize">
                            {statusInfo.label}
                          </Badge>
                        </div>
                      </div>
                      {lastMsg && (
                        <p className="mt-2 text-sm text-muted-foreground line-clamp-1">
                          {lastMsg.senderType === "agent" ? (
                            <span className="font-medium text-foreground">Support: </span>
                          ) : (
                            <span className="font-medium text-foreground">You: </span>
                          )}
                          {lastMsg.message}
                        </p>
                      )}
                      <div className="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <Clock className="h-3 w-3" />
                        <span>Last update {formatDate(ticket.updatedAt, "relative")}</span>
                        <span className="font-medium">
                          ({ticket.messages.length} message{ticket.messages.length !== 1 ? "s" : ""})
                        </span>
                      </div>
                    </div>
                    <ChevronRight className="h-5 w-5 text-muted-foreground shrink-0 mt-2" />
                  </div>
                )
              })}
            </div>
          ) : (
            <div className="flex flex-col items-center py-16 text-center">
              <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                <Headphones className="h-8 w-8 text-muted-foreground" />
              </div>
              <h3 className="text-lg font-medium text-foreground">No tickets yet</h3>
              <p className="text-sm text-muted-foreground mt-1">Create a support ticket and we'll get back to you</p>
              <Button className="mt-4" onClick={() => setCreateOpen(true)}>
                <Plus className="mr-2 h-4 w-4" />
                Create Ticket
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      <Dialog open={createOpen} onOpenChange={setCreateOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>Create Support Ticket</DialogTitle>
            <DialogDescription>Describe your issue and we'll respond promptly</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleCreate} className="space-y-4">
            <div className="space-y-2">
              <Label required>Subject</Label>
              <Input
                placeholder="Brief description of your issue"
                value={formData.subject}
                onChange={(e) => setFormData(prev => ({ ...prev, subject: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label required>Category</Label>
              <Select
                value={formData.category}
                onValueChange={(v) => setFormData(prev => ({ ...prev, category: v }))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select category" />
                </SelectTrigger>
                <SelectContent>
                  {categories.map(c => (
                    <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label required>Message</Label>
              <Textarea
                placeholder="Describe your issue in detail..."
                rows={5}
                value={formData.message}
                onChange={(e) => setFormData(prev => ({ ...prev, message: e.target.value }))}
              />
            </div>
            <DialogFooter className="pt-4">
              <DialogClose asChild>
                <Button type="button" variant="outline">Cancel</Button>
              </DialogClose>
              <Button type="submit" loading={createMutation.isPending}>
                {createMutation.isPending ? "Submitting..." : "Submit Ticket"}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
