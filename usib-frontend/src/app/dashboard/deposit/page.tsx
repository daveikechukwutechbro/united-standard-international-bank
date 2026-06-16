"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@tanstack/react-query"
import { motion, AnimatePresence } from "framer-motion"
import {
  Download, CheckCircle2, DollarSign, Building2,
  Copy, Clock, ExternalLink
} from "lucide-react"
import { api } from "@/lib/api"
import { formatCurrency } from "@/lib/utils"
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Badge } from "@/components/ui/badge"

export default function DepositPage() {
  const [step, setStep] = useState<"form" | "instructions" | "success">("form")
  const [accountId, setAccountId] = useState("")
  const [amount, setAmount] = useState("")
  const [method, setMethod] = useState("wire")
  const [instructions, setInstructions] = useState<any>(null)
  const [depositId, setDepositId] = useState("")
  const [copied, setCopied] = useState<string | null>(null)

  const { data: accounts } = useQuery({
    queryKey: ["accounts"],
    queryFn: api.getAccounts,
  })

  const depositMutation = useMutation({
    mutationFn: () => api.initiateDeposit(accountId, parseFloat(amount), method),
    onSuccess: (data) => {
      setInstructions(data.instructions)
      setDepositId(data.id)
      setStep("instructions")
    },
  })

  const selectedAccount = accounts?.find((a) => a.id === accountId)
  const parsedAmount = parseFloat(amount) || 0
  const canSubmit = accountId && parsedAmount > 0

  const copyToClipboard = (text: string, key: string) => {
    navigator.clipboard.writeText(text)
    setCopied(key)
    setTimeout(() => setCopied(null), 2000)
  }

  const handleConfirm = () => {
    setStep("success")
  }

  const reset = () => {
    setStep("form")
    setAccountId("")
    setAmount("")
    setMethod("wire")
    setInstructions(null)
    setDepositId("")
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="mx-auto max-w-2xl space-y-6"
    >
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Deposit Funds</h1>
        <p className="text-sm text-muted-foreground">Add money to your account</p>
      </div>

      <AnimatePresence mode="wait">
        {step === "success" ? (
          <motion.div
            key="success"
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.95 }}
          >
            <Card className="text-center">
              <CardContent className="pt-12 pb-8">
                <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-50 dark:bg-green-900/20">
                  <CheckCircle2 className="h-8 w-8 text-green-500" />
                </div>
                <h2 className="text-xl font-bold mb-2">Deposit Initiated!</h2>
                <p className="text-muted-foreground mb-2">
                  Your funds are on the way to your account.
                </p>
                <p className="text-sm text-muted-foreground mb-6">
                  Deposit ID: <span className="font-mono font-medium">{depositId}</span>
                </p>
                <div className="mx-auto max-w-sm rounded-lg bg-muted p-4 mb-6 text-left space-y-2">
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">Account</span>
                    <span className="text-sm font-medium">{selectedAccount?.accountName}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">Amount</span>
                    <span className="text-sm font-bold">{formatCurrency(parsedAmount)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">Method</span>
                    <Badge variant="outline" className="capitalize">{method}</Badge>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">Status</span>
                    <Badge variant="warning">Pending</Badge>
                  </div>
                </div>
                <div className="flex items-center justify-center gap-3">
                  <Button onClick={reset} variant="outline">Make Another Deposit</Button>
                  <Button onClick={() => window.location.href = "/dashboard"}>Back to Dashboard</Button>
                </div>
              </CardContent>
            </Card>
          </motion.div>
        ) : step === "instructions" ? (
          <motion.div
            key="instructions"
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -20 }}
          >
            <Card>
              <CardHeader>
                <CardTitle>Funding Instructions</CardTitle>
                <CardDescription>
                  Send {formatCurrency(parsedAmount)} using the details below
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-4 text-sm flex items-start gap-3">
                  <Clock className="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                  <div>
                    <p className="font-medium text-amber-800 dark:text-amber-200">Important</p>
                    <p className="text-amber-700 dark:text-amber-300">
                      Please include your reference number in the transfer description so we can identify your deposit.
                    </p>
                  </div>
                </div>

                {instructions && (
                  <div className="space-y-3 rounded-lg border p-4">
                    {[
                      { label: "Bank Name", value: instructions.bankName, key: "bank" },
                      { label: "Account Number", value: instructions.accountNumber, key: "acct" },
                      { label: "Routing Number", value: instructions.routingNumber, key: "routing" },
                      { label: "Reference", value: instructions.reference, key: "ref" },
                    ].map((item) => (
                      <div key={item.key} className="flex items-center justify-between">
                        <div>
                          <p className="text-xs text-muted-foreground">{item.label}</p>
                          <p className="text-sm font-medium font-mono">{item.value}</p>
                        </div>
                        <Button
                          variant="ghost"
                          size="sm"
                          className="h-8 gap-1"
                          onClick={() => copyToClipboard(item.value, item.key)}
                        >
                          <Copy className="h-3.5 w-3.5" />
                          {copied === item.key ? "Copied!" : "Copy"}
                        </Button>
                      </div>
                    ))}
                  </div>
                )}

                <div className="rounded-lg bg-muted p-4 text-sm space-y-1">
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Crediting Account</span>
                    <span className="font-medium">{selectedAccount?.accountName}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Amount</span>
                    <span className="font-bold">{formatCurrency(parsedAmount)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Method</span>
                    <span className="capitalize font-medium">{method}</span>
                  </div>
                </div>
              </CardContent>
              <CardFooter className="flex gap-3">
                <Button variant="outline" onClick={() => setStep("form")} className="flex-1">
                  Edit
                </Button>
                <Button onClick={handleConfirm} className="flex-1">
                  I&apos;ve Sent the Funds
                </Button>
              </CardFooter>
            </Card>
          </motion.div>
        ) : (
          <motion.div
            key="form"
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: 20 }}
          >
            <Card>
              <CardHeader>
                <CardTitle>Deposit Details</CardTitle>
                <CardDescription>Select where you want the money to go</CardDescription>
              </CardHeader>
              <CardContent className="space-y-5">
                <div className="space-y-2">
                  <Label>Destination Account</Label>
                  <Select value={accountId} onValueChange={setAccountId}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select account" />
                    </SelectTrigger>
                    <SelectContent>
                      {accounts?.map((a) => (
                        <SelectItem key={a.id} value={a.id}>
                          {a.accountName} — {formatCurrency(a.balance, a.currency)}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label>Amount</Label>
                  <div className="relative">
                    <DollarSign className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      type="number"
                      placeholder="0.00"
                      value={amount}
                      onChange={(e) => setAmount(e.target.value)}
                      className="pl-9"
                      min="0"
                      step="0.01"
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label>Funding Method</Label>
                  <Select value={method} onValueChange={setMethod}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="wire">Wire Transfer</SelectItem>
                      <SelectItem value="check">Check Deposit</SelectItem>
                      <SelectItem value="internal">Internal Transfer</SelectItem>
                    </SelectContent>
                  </Select>
                  <p className="text-xs text-muted-foreground">
                    {method === "wire" && "Funds arrive within 1-2 business days"}
                    {method === "check" && "Check must clear, typically 3-5 business days"}
                    {method === "internal" && "Instant transfer from another USIB account"}
                  </p>
                </div>

                {selectedAccount && parsedAmount > 0 && (
                  <div className="rounded-lg border p-4 space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Depositing To</span>
                      <span className="font-medium">{selectedAccount.accountName}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Amount</span>
                      <span className="font-bold">{formatCurrency(parsedAmount)}</span>
                    </div>
                    {selectedAccount.currency !== "USD" && (
                      <div className="flex justify-between text-amber-600">
                        <span>Currency</span>
                        <span>{selectedAccount.currency}</span>
                      </div>
                    )}
                  </div>
                )}
              </CardContent>
              <CardFooter>
                <Button
                  className="w-full gap-2"
                  disabled={!canSubmit || depositMutation.isPending}
                  loading={depositMutation.isPending}
                  onClick={() => depositMutation.mutate()}
                >
                  <Download className="h-4 w-4" />
                  Generate Funding Instructions
                </Button>
              </CardFooter>
            </Card>
          </motion.div>
        )}
      </AnimatePresence>
    </motion.div>
  )
}
