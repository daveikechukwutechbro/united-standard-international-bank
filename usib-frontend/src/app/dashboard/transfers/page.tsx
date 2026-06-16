"use client"

import { useState } from "react"
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query"
import { motion, AnimatePresence } from "framer-motion"
import {
  ArrowUpDown, CheckCircle2, Building2, User, ChevronDown,
  ArrowRight, AlertCircle, Clock, DollarSign, Search,
  UserCheck, UserX, Loader2
} from "lucide-react"
import { api } from "@/lib/api"
import { formatCurrency, cn } from "@/lib/utils"
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Badge } from "@/components/ui/badge"

type TransferTab = "internal" | "domestic" | "international"

export default function TransfersPage() {
  const [tab, setTab] = useState<TransferTab>("internal")
  const [step, setStep] = useState<"form" | "review" | "success">("form")
  const [fromAccount, setFromAccount] = useState("")
  const [toAccountNumber, setToAccountNumber] = useState("")
  const [beneficiaryId, setBeneficiaryId] = useState("")
  const [amount, setAmount] = useState("")
  const [description, setDescription] = useState("")
  const [reference, setReference] = useState("")

  const queryClient = useQueryClient()

  const { data: accounts } = useQuery({
    queryKey: ["accounts"],
    queryFn: api.getAccounts,
  })

  const { data: beneficiaries } = useQuery({
    queryKey: ["beneficiaries"],
    queryFn: api.getBeneficiaries,
  })

  const lookupQuery = useQuery({
    queryKey: ["recipient-lookup", toAccountNumber],
    queryFn: () => api.lookupRecipient(toAccountNumber),
    enabled: tab === "internal" && toAccountNumber.length >= 10,
    retry: false,
    staleTime: 30000,
  })

  const transferMutation = useMutation({
    mutationFn: (data: { fromAccountId: string; toAccountNumber: string; amount: number; description?: string; isInternal: boolean }) =>
      api.createTransfer(data),
    onSuccess: (data) => {
      setReference(data.reference)
      setStep("success")
      queryClient.invalidateQueries({ queryKey: ["transactions"] })
      queryClient.invalidateQueries({ queryKey: ["accounts"] })
      queryClient.invalidateQueries({ queryKey: ["dashboard-summary"] })
      queryClient.invalidateQueries({ queryKey: ["notifications"] })
    },
  })

  const isInternal = tab === "internal"
  const fromAcct = accounts?.find((a) => a.id === fromAccount)
  const selectedBeneficiary = beneficiaries?.find((b) => b.id === beneficiaryId)

  const recipient = lookupQuery.data?.exists ? lookupQuery.data : null
  const lookupError = lookupQuery.data && !lookupQuery.data.exists && toAccountNumber.length >= 10
    ? "Account not found. Please verify the account number."
    : null

  const fee = isInternal ? 0 : 15
  const parsedAmount = parseFloat(amount) || 0
  const total = parsedAmount + fee

  const canReview = fromAccount && parsedAmount > 0 && (
    isInternal ? (recipient !== null) : !!beneficiaryId
  )

  const handleSubmit = () => {
    if (!fromAccount || !parsedAmount) return
    const toAcctNum = isInternal
      ? toAccountNumber
      : (selectedBeneficiary?.accountNumber ?? "")
    transferMutation.mutate({
      fromAccountId: fromAccount,
      toAccountNumber: toAcctNum,
      amount: parsedAmount,
      description: description || undefined,
      isInternal,
    })
  }

  const reset = () => {
    setStep("form")
    setFromAccount("")
    setToAccountNumber("")
    setBeneficiaryId("")
    setAmount("")
    setDescription("")
    setReference("")
  }

  const tabs: { key: TransferTab; label: string }[] = [
    { key: "internal", label: "Internal Transfer" },
    { key: "domestic", label: "Domestic Wire" },
    { key: "international", label: "International Wire" },
  ]

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="mx-auto max-w-2xl space-y-6"
    >
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Transfers</h1>
        <p className="text-sm text-muted-foreground">Send money securely to any account</p>
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
                <h2 className="text-xl font-bold mb-2">Transfer Initiated!</h2>
                <p className="text-muted-foreground mb-4">
                  Your transfer of {formatCurrency(parsedAmount)} has been processed.
                </p>
                <div className="mx-auto mb-6 inline-flex items-center gap-2 rounded-lg bg-muted px-4 py-2">
                  <span className="text-sm text-muted-foreground">Reference:</span>
                  <span className="font-mono font-medium">{reference}</span>
                </div>
                <div className="flex items-center justify-center gap-2 text-sm text-muted-foreground mb-6">
                  <Clock className="h-4 w-4" />
                  <span>Estimated delivery: {isInternal ? "Immediate" : "1-2 business days"}</span>
                </div>
                <div className="flex items-center justify-center gap-3">
                  <Button onClick={reset} variant="outline">Make Another Transfer</Button>
                  <Button onClick={() => window.location.href = "/dashboard"}>Back to Dashboard</Button>
                </div>
              </CardContent>
            </Card>
          </motion.div>
        ) : step === "review" ? (
          <motion.div
            key="review"
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -20 }}
            className="space-y-6"
          >
            <Card>
              <CardHeader>
                <CardTitle>Review Transfer</CardTitle>
                <CardDescription>Please verify the details before confirming</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="rounded-lg bg-muted p-4">
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm text-muted-foreground">From</span>
                    <span className="font-medium">{fromAcct?.accountName}</span>
                  </div>
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm text-muted-foreground">To</span>
                    <span className="font-medium">
                      {isInternal
                        ? recipient?.holderName
                        : selectedBeneficiary?.name}
                    </span>
                  </div>
                  {isInternal && recipient && (
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-sm text-muted-foreground">Recipient Account</span>
                      <span className="font-mono text-sm">{recipient.accountNumber}</span>
                    </div>
                  )}
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm text-muted-foreground">Amount</span>
                    <span className="text-lg font-bold">{formatCurrency(parsedAmount)}</span>
                  </div>
                  {fee > 0 && (
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-sm text-muted-foreground">Fee</span>
                      <span className="font-medium">{formatCurrency(fee)}</span>
                    </div>
                  )}
                  <div className="flex items-center justify-between pt-2 border-t">
                    <span className="text-sm font-medium">Total</span>
                    <span className="text-lg font-bold text-gold-500">{formatCurrency(total)}</span>
                  </div>
                </div>
                {description && (
                  <p className="text-sm text-muted-foreground">
                    Note: {description}
                  </p>
                )}
                <div className="flex items-center gap-2 text-sm text-amber-600 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
                  <AlertCircle className="h-4 w-4 shrink-0" />
                  <span>Please verify all details. Transfers cannot be reversed once confirmed.</span>
                </div>
              </CardContent>
              <CardFooter className="flex gap-3">
                <Button variant="outline" onClick={() => setStep("form")} className="flex-1">
                  Edit
                </Button>
                <Button onClick={handleSubmit} loading={transferMutation.isPending} className="flex-1">
                  Confirm Transfer
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
                <div className="flex gap-2">
                  {tabs.map((t) => (
                    <Button
                      key={t.key}
                      variant={tab === t.key ? "default" : "outline"}
                      size="sm"
                      onClick={() => { setTab(t.key); setStep("form") }}
                      className="text-xs"
                    >
                      {t.label}
                    </Button>
                  ))}
                </div>
              </CardHeader>
              <CardContent className="space-y-5">
                <div className="space-y-2">
                  <Label>From Account</Label>
                  <Select value={fromAccount} onValueChange={setFromAccount}>
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

                {isInternal ? (
                  <div className="space-y-2">
                    <Label>Recipient Account Number</Label>
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                      <Input
                        placeholder="Enter account number (e.g. USIB4002005005)"
                        value={toAccountNumber}
                        onChange={(e) => setToAccountNumber(e.target.value)}
                        className="pl-9 font-mono text-sm"
                      />
                    </div>
                    <p className="text-xs text-muted-foreground">
                      Enter the recipient&apos;s USIB account number to auto-fetch their details
                    </p>

                    {toAccountNumber.length >= 10 && lookupQuery.isLoading && (
                      <div className="flex items-center gap-2 rounded-lg bg-muted p-3 text-sm">
                        <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
                        <span className="text-muted-foreground">Looking up account...</span>
                      </div>
                    )}

                    {lookupError && (
                      <div className="flex items-center gap-2 rounded-lg bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-600">
                        <UserX className="h-4 w-4 shrink-0" />
                        <span>{lookupError}</span>
                      </div>
                    )}

                    {recipient && (
                      <div className="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/10 p-3 space-y-2">
                        <div className="flex items-center gap-2 text-green-600 dark:text-green-400">
                          <UserCheck className="h-4 w-4" />
                          <span className="text-sm font-medium">Recipient Found</span>
                        </div>
                        <div className="space-y-1 text-sm">
                          <div className="flex justify-between">
                            <span className="text-muted-foreground">Name</span>
                            <span className="font-medium">{recipient.holderName}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-muted-foreground">Account</span>
                            <span className="font-mono">{recipient.accountNumber}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-muted-foreground">Account Type</span>
                            <span className="capitalize">{recipient.accountType.replace("_", " ")}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-muted-foreground">Currency</span>
                            <span>{recipient.currency}</span>
                          </div>
                        </div>
                      </div>
                    )}

                    {fromAccount && recipient && fromAcct && recipient.accountNumber === fromAcct.accountNumber && (
                      <div className="flex items-center gap-2 rounded-lg bg-amber-50 dark:bg-amber-900/20 p-3 text-sm text-amber-600">
                        <AlertCircle className="h-4 w-4 shrink-0" />
                        <span>You cannot transfer to your own account. Please enter a different account number.</span>
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="space-y-2">
                    <Label>Beneficiary</Label>
                    <Select value={beneficiaryId} onValueChange={setBeneficiaryId}>
                      <SelectTrigger>
                        <SelectValue placeholder="Select beneficiary" />
                      </SelectTrigger>
                      <SelectContent>
                        {beneficiaries?.map((b) => (
                          <SelectItem key={b.id} value={b.id}>
                            {b.name} — {b.bankName}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {selectedBeneficiary && (
                      <div className="rounded-lg bg-muted p-3 text-sm space-y-1">
                        <div className="flex justify-between">
                          <span className="text-muted-foreground">Account</span>
                          <span className="font-mono">{selectedBeneficiary.accountNumber}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-muted-foreground">Bank</span>
                          <span>{selectedBeneficiary.bankName}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-muted-foreground">Currency</span>
                          <span>{selectedBeneficiary.currency}</span>
                        </div>
                      </div>
                    )}
                    <Button variant="link" size="sm" className="h-auto p-0 text-xs">
                      + Add New Beneficiary
                    </Button>
                  </div>
                )}

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
                  <Label>Description (Optional)</Label>
                  <Input
                    placeholder="What's this for?"
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                  />
                </div>

                {(fromAcct && parsedAmount > 0) && (
                  <div className="rounded-lg border p-4 space-y-2">
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-muted-foreground">Transfer Fee</span>
                      <span className="font-medium">{fee === 0 ? "Free" : formatCurrency(fee)}</span>
                    </div>
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-muted-foreground">Estimated Delivery</span>
                      <span className="font-medium flex items-center gap-1">
                        <Clock className="h-3.5 w-3.5" />
                        {isInternal ? "Immediate" : "1-2 business days"}
                      </span>
                    </div>
                    <div className="flex items-center justify-between text-sm pt-2 border-t">
                      <span className="font-medium">Total Amount</span>
                      <span className="font-semibold">{formatCurrency(total)}</span>
                    </div>
                  </div>
                )}
              </CardContent>
              <CardFooter>
                <Button
                  className="w-full gap-2"
                  disabled={!canReview}
                  onClick={() => setStep("review")}
                >
                  Review Transfer
                  <ArrowRight className="h-4 w-4" />
                </Button>
              </CardFooter>
            </Card>
          </motion.div>
        )}
      </AnimatePresence>
    </motion.div>
  )
}
