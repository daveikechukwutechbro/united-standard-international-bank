"use client"

import { useState, useCallback } from "react"
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query"
import {
  RefreshCw, ArrowRightLeft, ArrowDownUp, DollarSign, Euro, PoundSterling,
  SwissFranc, CircleDot, AlertCircle, CheckCircle2, ChevronRight, Clock,
  ArrowLeftRight, History
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

const currencies = [
  { code: "USD", name: "US Dollar", symbol: "$", icon: DollarSign },
  { code: "EUR", name: "Euro", symbol: "€", icon: Euro },
  { code: "GBP", name: "British Pound", symbol: "£", icon: PoundSterling },
  { code: "CHF", name: "Swiss Franc", symbol: "Fr", icon: SwissFranc },
  { code: "JPY", name: "Japanese Yen", symbol: "¥", icon: CircleDot },
]

export default function ExchangePage() {
  const queryClient = useQueryClient()
  const { show, Toast } = useToast()
  const [fromCurrency, setFromCurrency] = useState("USD")
  const [toCurrency, setToCurrency] = useState("EUR")
  const [amount, setAmount] = useState("1000")
  const [confirmOpen, setConfirmOpen] = useState(false)

  const { data: quote, isLoading: quoteLoading, isFetching: quoteFetching, refetch: refetchQuote } = useQuery({
    queryKey: ["exchange-quote", fromCurrency, toCurrency, amount],
    queryFn: () => api.getExchangeQuote(fromCurrency, toCurrency, parseFloat(amount) || 0),
    enabled: !!amount && parseFloat(amount) > 0,
  })

  const swapMutation = useMutation({
    mutationFn: () => api.createSwap({
      fromAccountId: "ACC-001",
      toAccountId: "ACC-002",
      fromCurrency,
      toCurrency,
      amount: parseFloat(amount),
      quoteId: quote?.quoteId,
    }),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ["accounts"] })
      setConfirmOpen(false)
      setAmount("")
      show(`Swap completed! Received ${formatCurrency(data.convertedAmount, toCurrency)}`, "success")
    },
    onError: (error: Error) => {
      show(error.message || "Swap failed", "error")
    },
  })

  const { data: swaps, isLoading: swapsLoading } = useQuery({
    queryKey: ["swaps"],
    queryFn: async () => {
      const txs = await api.getTransactions()
      return txs.filter(t => t.type === "exchange").slice(0, 10)
    },
  })

  const handleSwap = useCallback(() => {
    if (fromCurrency === toCurrency) {
      show("Cannot swap same currency", "error")
      return
    }
    if (!amount || parseFloat(amount) <= 0) {
      show("Enter a valid amount", "error")
      return
    }
    setConfirmOpen(true)
  }, [fromCurrency, toCurrency, amount, show])

  const handleConfirm = useCallback(() => {
    swapMutation.mutate()
  }, [swapMutation])

  const swapCurrencies = useCallback(() => {
    setFromCurrency(toCurrency)
    setToCurrency(fromCurrency)
  }, [fromCurrency, toCurrency])

  return (
    <div className="space-y-8">
      {Toast}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold font-display text-foreground">Currency Exchange</h1>
          <p className="text-sm text-muted-foreground">Exchange currencies at competitive rates</p>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Exchange Currencies</CardTitle>
              <CardDescription>Convert between major world currencies</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid gap-4 md:grid-cols-[1fr,auto,1fr]">
                <div className="space-y-2">
                  <Label>From</Label>
                  <Select value={fromCurrency} onValueChange={setFromCurrency}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {currencies.map(c => (
                        <SelectItem key={c.code} value={c.code}>
                          <div className="flex items-center gap-2">
                            <c.icon className="h-4 w-4" />
                            <span>{c.code} - {c.name}</span>
                          </div>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="flex items-end justify-center pb-2">
                  <Button variant="ghost" size="icon" onClick={swapCurrencies} className="h-10 w-10 rounded-full">
                    <ArrowDownUp className="h-5 w-5" />
                  </Button>
                </div>
                <div className="space-y-2">
                  <Label>To</Label>
                  <Select value={toCurrency} onValueChange={setToCurrency}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {currencies.map(c => (
                        <SelectItem key={c.code} value={c.code}>
                          <div className="flex items-center gap-2">
                            <c.icon className="h-4 w-4" />
                            <span>{c.code} - {c.name}</span>
                          </div>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="space-y-2">
                <Label>Amount</Label>
                <div className="relative">
                  <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground font-medium">
                    {currencies.find(c => c.code === fromCurrency)?.symbol || "$"}
                  </span>
                  <Input
                    type="number"
                    placeholder="Enter amount"
                    value={amount}
                    onChange={(e) => setAmount(e.target.value)}
                    className="pl-8"
                  />
                </div>
              </div>

              {quote && (
                <div className="rounded-xl border bg-gradient-to-br from-usib-50 to-gold-50 p-5 space-y-3">
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Exchange Rate</span>
                    <div className="flex items-center gap-2">
                      <span className="text-sm font-semibold text-foreground">
                        1 {fromCurrency} = {quote.rate} {toCurrency}
                      </span>
                      <button onClick={() => refetchQuote()} disabled={quoteFetching}>
                        <RefreshCw className={cn("h-3.5 w-3.5 text-muted-foreground", quoteFetching && "animate-spin")} />
                      </button>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Fee (0.5%)</span>
                    <span className="text-sm font-medium text-muted-foreground">{formatCurrency(quote.fee, fromCurrency)}</span>
                  </div>
                  <div className="border-t pt-3">
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-muted-foreground">You Receive</span>
                      <span className="text-xl font-bold text-foreground">
                        {formatCurrency(quote.estimatedAmount, toCurrency)}
                      </span>
                    </div>
                  </div>
                  <div className="flex items-center gap-1 text-xs text-muted-foreground">
                    <Clock className="h-3 w-3" />
                    Valid until {formatDate(quote.validUntil, "short")}
                  </div>
                </div>
              )}

              {quoteLoading && (
                <div className="space-y-2">
                  <Skeleton className="h-5 w-48" />
                  <Skeleton className="h-5 w-32" />
                  <Skeleton className="h-8 w-64" />
                </div>
              )}

              <Button
                className="w-full"
                size="lg"
                onClick={handleSwap}
                disabled={!quote || fromCurrency === toCurrency || !amount || parseFloat(amount) <= 0}
              >
                <ArrowLeftRight className="mr-2 h-4 w-4" />
                Exchange {fromCurrency} to {toCurrency}
              </Button>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Recent Swaps</CardTitle>
              <CardDescription>Your recent currency exchange transactions</CardDescription>
            </CardHeader>
            <CardContent className="p-0">
              {swapsLoading ? (
                <div className="p-6 space-y-3">
                  {[1, 2, 3].map(i => <Skeleton key={i} className="h-12 w-full" />)}
                </div>
              ) : swaps && swaps.length > 0 ? (
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b text-left text-xs font-medium text-muted-foreground">
                        <th className="px-6 py-3">Date</th>
                        <th className="px-6 py-3">Description</th>
                        <th className="px-6 py-3">Amount</th>
                        <th className="px-6 py-3">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {swaps.map((tx) => (
                        <tr key={tx.id} className="border-b last:border-0 hover:bg-muted/50">
                          <td className="px-6 py-4 text-muted-foreground">{formatDate(tx.createdAt)}</td>
                          <td className="px-6 py-4 font-medium text-foreground">{tx.description}</td>
                          <td className="px-6 py-4 text-foreground">{formatCurrency(tx.amount, tx.currency)}</td>
                          <td className="px-6 py-4">
                            <Badge variant={tx.status === "completed" ? "success" : tx.status === "pending" ? "warning" : "danger"}>
                              {tx.status}
                            </Badge>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="flex flex-col items-center py-12 text-center">
                  <History className="mb-2 h-8 w-8 text-muted-foreground" />
                  <p className="text-sm font-medium text-foreground">No swaps yet</p>
                  <p className="text-xs text-muted-foreground">Your currency exchange history will appear here</p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Live Rates</CardTitle>
              <CardDescription>Current exchange rates</CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              {currencies.filter(c => c.code !== fromCurrency).map((c) => {
                const pairKey = `${fromCurrency}/${c.code}`
                return (
                  <div key={c.code} className="flex items-center justify-between rounded-lg bg-muted/50 p-3">
                    <div className="flex items-center gap-2">
                      <c.icon className="h-4 w-4 text-muted-foreground" />
                      <span className="text-sm font-medium text-foreground">{c.code}</span>
                    </div>
                    <span className="text-sm text-muted-foreground">
                      1 {fromCurrency} = {Math.random() > 0.5 ? (0.8 + Math.random() * 0.3).toFixed(4) : (100 + Math.random() * 50).toFixed(2)} {c.code}
                    </span>
                  </div>
                )
              })}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Exchange Info</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
              <div className="flex items-start gap-3">
                <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                  <CheckCircle2 className="h-3.5 w-3.5" />
                </div>
                <p className="text-muted-foreground">Competitive rates with 0.5% fee</p>
              </div>
              <div className="flex items-start gap-3">
                <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                  <CheckCircle2 className="h-3.5 w-3.5" />
                </div>
                <p className="text-muted-foreground">No hidden charges or markups</p>
              </div>
              <div className="flex items-start gap-3">
                <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                  <CheckCircle2 className="h-3.5 w-3.5" />
                </div>
                <p className="text-muted-foreground">Instant execution on market rates</p>
              </div>
              <div className="flex items-start gap-3">
                <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                  <CheckCircle2 className="h-3.5 w-3.5" />
                </div>
                <p className="text-muted-foreground">Rates valid for 30 seconds</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Confirm Exchange</DialogTitle>
            <DialogDescription>Please review the details before confirming</DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="rounded-xl border p-4 space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">From</span>
                <span className="font-medium">{formatCurrency(parseFloat(amount), fromCurrency)}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">To</span>
                <span className="font-medium">{quote ? formatCurrency(quote.estimatedAmount, toCurrency) : ""}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Exchange Rate</span>
                <span className="font-medium">{quote ? `1 ${fromCurrency} = ${quote.rate} ${toCurrency}` : ""}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Fee</span>
                <span className="font-medium text-muted-foreground">{quote ? formatCurrency(quote.fee, fromCurrency) : ""}</span>
              </div>
              <div className="border-t pt-3 flex justify-between">
                <span className="text-sm font-medium">You will receive</span>
                <span className="text-lg font-bold text-gold-500">
                  {quote ? formatCurrency(quote.estimatedAmount, toCurrency) : ""}
                </span>
              </div>
            </div>
            <p className="text-xs text-muted-foreground">
              By confirming, you agree to execute this currency exchange at the quoted rate.
            </p>
          </div>
          <DialogFooter>
            <DialogClose asChild>
              <Button variant="outline">Cancel</Button>
            </DialogClose>
            <Button onClick={handleConfirm} loading={swapMutation.isPending}>
              {swapMutation.isPending ? "Processing..." : "Confirm Exchange"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
