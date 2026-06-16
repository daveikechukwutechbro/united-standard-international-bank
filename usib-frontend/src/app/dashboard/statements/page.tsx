"use client"

import { useState } from "react"
import { useQuery } from "@tanstack/react-query"
import {
  FileText, Download, Calendar, Search, ChevronRight,
  FileDown, FileSpreadsheet, Printer, Loader2, CheckCircle2,
  Clock, AlertCircle, Building2
} from "lucide-react"
import { api } from "@/lib/api"
import { formatCurrency, formatDate, cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"
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

const mockStatements = [
  { id: "STMT-001", account: "Everyday Checking", accountNumber: "USIB4002001001", period: "Dec 1 - Dec 31, 2024", date: "2025-01-01", type: "monthly", size: "245 KB" },
  { id: "STMT-002", account: "Everyday Checking", accountNumber: "USIB4002001001", period: "Nov 1 - Nov 30, 2024", date: "2024-12-01", type: "monthly", size: "230 KB" },
  { id: "STMT-003", account: "Everyday Checking", accountNumber: "USIB4002001001", period: "Oct 1 - Oct 31, 2024", date: "2024-11-01", type: "monthly", size: "218 KB" },
  { id: "STMT-004", account: "High-Yield Savings", accountNumber: "USIB4002002002", period: "Q4 2024", date: "2025-01-01", type: "quarterly", size: "180 KB" },
  { id: "STMT-005", account: "Business Account", accountNumber: "USIB4002003003", period: "Dec 1 - Dec 31, 2024", date: "2025-01-01", type: "monthly", size: "156 KB" },
  { id: "STMT-006", account: "Everyday Checking", accountNumber: "USIB4002001001", period: "Jan 1 - Dec 31, 2024", date: "2025-01-01", type: "annual", size: "1.2 MB" },
]

export default function StatementsPage() {
  const { show, Toast } = useToast()
  const [selectedAccount, setSelectedAccount] = useState("all")
  const [dateFrom, setDateFrom] = useState("")
  const [dateTo, setDateTo] = useState("")
  const [generateOpen, setGenerateOpen] = useState(false)
  const [genForm, setGenForm] = useState({ accountId: "", period: "custom", fromDate: "", toDate: "" })
  const [generating, setGenerating] = useState(false)

  const { data: accounts } = useQuery({
    queryKey: ["accounts"],
    queryFn: api.getAccounts,
  })

  const filtered = mockStatements.filter(s =>
    (selectedAccount === "all" || s.accountNumber === selectedAccount)
  )

  const handleGenerate = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!genForm.accountId || !genForm.fromDate || !genForm.toDate) {
      show("Please select an account and date range", "error")
      return
    }
    setGenerating(true)
    await new Promise(r => setTimeout(r, 2000))
    setGenerating(false)
    setGenerateOpen(false)
    setGenForm({ accountId: "", period: "custom", fromDate: "", toDate: "" })
    show("Statement generated successfully", "success")
  }

  const handleDownload = (format: "pdf" | "csv") => {
    show(`Statement downloaded as ${format.toUpperCase()}`, "success")
  }

  return (
    <div className="space-y-8">
      {Toast}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold font-display text-foreground">Statements</h1>
          <p className="text-sm text-muted-foreground">View and download your account statements</p>
        </div>
        <Button onClick={() => setGenerateOpen(true)}>
          <FileText className="mr-2 h-4 w-4" />
          Generate Statement
        </Button>
      </div>

      <Card>
        <CardContent className="p-6">
          <div className="flex flex-wrap items-end gap-4">
            <div className="space-y-2 min-w-[200px]">
              <Label>Account</Label>
              <Select value={selectedAccount} onValueChange={setSelectedAccount}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Accounts</SelectItem>
                  {accounts?.map(a => (
                    <SelectItem key={a.id} value={a.accountNumber}>
                      {a.accountName} ({a.accountNumber})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>From</Label>
              <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>To</Label>
              <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
            </div>
            <Button variant="secondary">
              <Search className="mr-2 h-4 w-4" />
              Filter
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Available Statements</CardTitle>
          <CardDescription>Download your account statements in PDF or CSV format</CardDescription>
        </CardHeader>
        <CardContent className="p-0">
          {filtered.length > 0 ? (
            <div className="divide-y">
              {filtered.map((stmt) => (
                <div key={stmt.id} className="flex items-center gap-4 px-6 py-4 hover:bg-muted/50">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-usib-100 text-usib-600">
                    <FileText className="h-5 w-5" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-foreground">{stmt.account}</p>
                    <p className="text-xs text-muted-foreground">
                      {stmt.period} • {stmt.accountNumber}
                    </p>
                    <div className="flex items-center gap-3 mt-1">
                      <Badge variant="secondary" className="text-[10px] capitalize">{stmt.type}</Badge>
                      <span className="text-[10px] text-muted-foreground">{stmt.size}</span>
                      <span className="text-[10px] text-muted-foreground">Generated {formatDate(stmt.date)}</span>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => handleDownload("pdf")}>
                      <FileDown className="mr-2 h-4 w-4" />
                      PDF
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => handleDownload("csv")}>
                      <FileSpreadsheet className="mr-2 h-4 w-4" />
                      CSV
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="flex flex-col items-center py-16 text-center">
              <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                <FileText className="h-8 w-8 text-muted-foreground" />
              </div>
              <h3 className="text-lg font-medium text-foreground">No statements found</h3>
              <p className="text-sm text-muted-foreground">Try adjusting your filters or generate a new statement</p>
            </div>
          )}
        </CardContent>
      </Card>

      <Dialog open={generateOpen} onOpenChange={setGenerateOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Generate Statement</DialogTitle>
            <DialogDescription>Create a new account statement for a specific period</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleGenerate} className="space-y-4">
            <div className="space-y-2">
              <Label required>Account</Label>
              <Select
                value={genForm.accountId}
                onValueChange={(v) => setGenForm(prev => ({ ...prev, accountId: v }))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select account" />
                </SelectTrigger>
                <SelectContent>
                  {accounts?.map(a => (
                    <SelectItem key={a.id} value={a.id}>
                      {a.accountName} ({formatCurrency(a.balance, a.currency)})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Period</Label>
              <Select
                value={genForm.period}
                onValueChange={(v) => setGenForm(prev => ({ ...prev, period: v }))}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="custom">Custom Range</SelectItem>
                  <SelectItem value="this-month">This Month</SelectItem>
                  <SelectItem value="last-month">Last Month</SelectItem>
                  <SelectItem value="last-3-months">Last 3 Months</SelectItem>
                  <SelectItem value="this-year">This Year</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label required>From Date</Label>
                <Input
                  type="date"
                  value={genForm.fromDate}
                  onChange={(e) => setGenForm(prev => ({ ...prev, fromDate: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <Label required>To Date</Label>
                <Input
                  type="date"
                  value={genForm.toDate}
                  onChange={(e) => setGenForm(prev => ({ ...prev, toDate: e.target.value }))}
                />
              </div>
            </div>
            <DialogFooter className="pt-4">
              <DialogClose asChild>
                <Button type="button" variant="outline">Cancel</Button>
              </DialogClose>
              <Button type="submit" loading={generating}>
                {generating ? "Generating..." : "Generate Statement"}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  )
}
