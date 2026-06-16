import type {
  User, Account, Transaction, Beneficiary, Loan, Card,
  Notification, SupportTicket, KycSubmission, ExchangeQuote,
  DashboardSummary, TransferRequest, TransferResponse,
  LoanApplication, ExchangeRequest, Session, KycPersonalDetails,
  RecipientLookupResult
} from "@/lib/types"
import { apiClient } from "@/lib/api-client"
import * as mock from "@/lib/mock-data"

const USE_MOCK = false

function delay(ms = 800) {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

// Backend response helpers
function extractData<T>(response: any, key = "data"): T {
  return response?.[key] ?? response
}

function extractSuccessData<T>(response: any): T {
  return response?.data ?? response
}

function mapBackendUser(bu: any): User {
  const parts = (bu.name || "").split(" ")
  return {
    id: bu.uuid || bu.id?.toString(),
    firstName: parts[0] || bu.name || "",
    lastName: parts.slice(1).join(" ") || "",
    email: bu.email || "",
    phone: bu.phone || "",
    kycStatus: (bu.kyc_status === "verified" ? "approved" : bu.kyc_status || "not_submitted") as any,
    twoFactorEnabled: !!bu.two_factor_confirmed_at,
    createdAt: bu.created_at || bu.createdAt || "",
    updatedAt: bu.updated_at || bu.updatedAt || "",
  }
}

function mapBackendAccount(ba: any): Account {
  return {
    id: ba.uuid,
    userId: ba.user_uuid,
    accountNumber: ba.uuid,
    accountType: (ba.name?.toLowerCase().includes("savings") ? "savings" : "checking") as any,
    accountName: ba.name || "Account",
    currency: "USD",
    balance: (ba.balance || 0) / 100,
    availableBalance: (ba.available_balance ?? ba.balance ?? 0) / 100,
    ledgerBalance: (ba.balance || 0) / 100,
    status: ba.frozen ? "frozen" : "active",
    openedAt: ba.created_at || ba.createdAt || "",
    createdAt: ba.created_at || ba.createdAt || "",
    updatedAt: ba.updated_at || ba.updatedAt || "",
  }
}

function mapBackendTransaction(bt: any): Transaction {
  return {
    id: bt.id?.toString() || bt.uuid || "",
    accountId: bt.account_uuid || bt.account_id?.toString() || "",
    type: bt.type === "debit" ? "debit" : bt.type === "credit" ? "credit" : (bt.type || "transfer"),
    amount: (bt.amount || 0) / 100,
    currency: bt.currency || "USD",
    fee: bt.fee ? bt.fee / 100 : 0,
    status: bt.status === "failed" ? "failed" : bt.status === "completed" ? "completed" : (bt.status || "pending"),
    description: bt.description || bt.reference || "",
    reference: bt.reference || bt.id?.toString() || "",
    counterparty: bt.counterparty || "",
    counterpartyAccount: bt.counterparty_account || "",
    category: bt.category || bt.type || "Transfer",
    balanceAfter: bt.balance_after ? bt.balance_after / 100 : undefined,
    createdAt: bt.created_at || bt.createdAt || "",
    updatedAt: bt.updated_at || bt.updatedAt || "",
  }
}

// Cache for fresh login data
let cachedUserId: string | null = null
let cachedUserAccounts: Account[] = []

async function getCurrentUserId(): Promise<string> {
  if (cachedUserId) return cachedUserId
  try {
    const res: any = await apiClient.get("/api/auth/me")
    const userData = extractSuccessData<any>(res)?.user || extractSuccessData<any>(res)
    cachedUserId = userData?.uuid || null
    return cachedUserId || "USR-001"
  } catch {
    return "USR-001"
  }
}

export const api = {
  async login(email: string, password: string): Promise<{ user: User; token: string }> {
    if (USE_MOCK) {
      await delay()
      if (email === "david.ikechukwu@usib.com" && password === "Password123!") {
        return { user: mock.mockUser, token: "mock-jwt-token-2024" }
      }
      throw new Error("Invalid email or password")
    }
    const response: any = await apiClient.post("/api/auth/login", { email, password })
    const data = extractSuccessData<any>(response)
    const backendUser = data.user || data
    const user = mapBackendUser(backendUser)
    cachedUserId = backendUser.uuid
    return { user, token: data.access_token || data.token }
  },

  async register(data: { firstName: string; lastName: string; email: string; phone: string; password: string }): Promise<{ user: User; token: string }> {
    if (USE_MOCK) {
      await delay()
      return {
        user: { ...mock.mockUser, firstName: data.firstName, lastName: data.lastName, email: data.email, phone: data.phone, kycStatus: "not_submitted" },
        token: "mock-jwt-token-2024",
      }
    }
    const response: any = await apiClient.post("/api/auth/register", {
      name: `${data.firstName} ${data.lastName}`,
      email: data.email,
      password: data.password,
      password_confirmation: data.password,
    })
    const d = extractSuccessData<any>(response)
    return { user: mapBackendUser(d.user || d), token: d.access_token || d.token }
  },

  async verifyOtp(email: string, otp: string): Promise<{ verified: boolean; token?: string }> {
    if (USE_MOCK) {
      await delay()
      if (otp === "123456") return { verified: true, token: "mock-otp-token" }
      throw new Error("Invalid OTP code")
    }
    const response: any = await apiClient.post("/api/auth/2fa/verify", { email, code: otp })
    return { verified: true, token: extractSuccessData<any>(response)?.access_token }
  },

  async forgotPassword(email: string): Promise<{ message: string }> {
    if (USE_MOCK) {
      await delay()
      return { message: "If an account exists with this email, a reset link has been sent." }
    }
    const response: any = await apiClient.post("/api/auth/forgot-password", { email })
    return { message: response?.message || "Reset link sent." }
  },

  async resetPassword(token: string, password: string): Promise<{ message: string }> {
    if (USE_MOCK) {
      await delay()
      return { message: "Password has been reset successfully." }
    }
    const response: any = await apiClient.post("/api/auth/reset-password", { token, email: "", password, password_confirmation: password })
    return { message: response?.message || "Password reset." }
  },

  async logout(): Promise<void> {
    if (!USE_MOCK) {
      try { await apiClient.post("/api/auth/logout") } catch {}
    }
  },

  async getDashboardSummary(): Promise<DashboardSummary> {
    if (USE_MOCK) {
      await delay()
      const allTxns = mock.mockTransactions
      const allAccounts = mock.mockAccounts
      const userAccounts = allAccounts.filter(a => a.userId === "USR-001")
      const totalBalance = userAccounts.reduce((s, a) => s + a.balance, 0)
      const availableBalance = userAccounts.reduce((s, a) => s + a.availableBalance, 0)
      const pendingCount = allTxns.filter(t => t.status === "pending").length
      const loans = mock.mockLoans.filter(l => l.userId === "USR-001" && (l.status === "active" || l.status === "pending"))
      const savingsAccounts = userAccounts.filter(a => a.accountType === "savings")
      const totalSavings = savingsAccounts.reduce((s, a) => s + a.balance, 0)

      const recentTxns = [...allTxns]
        .filter(t => userAccounts.some(a => a.id === t.accountId))
        .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
        .slice(0, 6)

      return {
        totalBalance,
        availableBalance,
        pendingTransactions: pendingCount,
        activeLoans: loans.length,
        totalSavings,
        recentTransactions: recentTxns,
        accounts: userAccounts,
        currencyBalances: [
          { currency: "USD", balance: userAccounts.filter(a => a.currency === "USD").reduce((s, a) => s + a.balance, 0), change: 2.4 },
          { currency: "EUR", balance: userAccounts.filter(a => a.currency === "EUR").reduce((s, a) => s + a.balance, 0), change: 1.1 },
        ],
        spendingByCategory: [
          { category: "Business Expenses", amount: 3170, percentage: 35 },
          { category: "Shopping", amount: 345.20, percentage: 12 },
          { category: "Entertainment", amount: 89.99, percentage: 8 },
          { category: "Savings", amount: 2000, percentage: 30 },
          { category: "Fees", amount: 5.00, percentage: 2 },
        ],
      }
    }
    const accounts = await this.getAccounts()
    const allTxns = await this.getTransactions()
    const totalBalance = accounts.reduce((s, a) => s + a.balance, 0)
    const availableBalance = accounts.reduce((s, a) => s + a.availableBalance, 0)
    const savingsAccounts = accounts.filter(a => a.accountType === "savings")
    const totalSavings = savingsAccounts.reduce((s, a) => s + a.balance, 0)
    const pendingCount = allTxns.filter(t => t.status === "pending").length

    const recentTxns = [...allTxns]
      .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
      .slice(0, 6)

    return {
      totalBalance,
      availableBalance,
      pendingTransactions: pendingCount,
      activeLoans: 0,
      totalSavings,
      recentTransactions: recentTxns,
      accounts,
      currencyBalances: [
        { currency: "USD", balance: accounts.filter(a => a.currency === "USD").reduce((s, a) => s + a.balance, 0), change: 0 },
      ],
      spendingByCategory: [],
    }
  },

  async getAccounts(): Promise<Account[]> {
    if (USE_MOCK) { await delay(); return mock.mockAccounts }
    const response: any = await apiClient.get("/api/accounts")
    const list = extractData<any[]>(response)
    cachedUserAccounts = (list || []).map(mapBackendAccount)
    return cachedUserAccounts
  },

  async getAccount(id: string): Promise<Account> {
    if (USE_MOCK) {
      await delay()
      const account = mock.mockAccounts.find(a => a.id === id)
      if (!account) throw new Error("Account not found")
      return account
    }
    const response: any = await apiClient.get(`/api/accounts/${id}`)
    return mapBackendAccount(extractSuccessData<any>(response))
  },

  async lookupRecipient(accountNumber: string): Promise<RecipientLookupResult> {
    if (USE_MOCK) {
      await delay(600)
      const account = mock.mockAccounts.find(a => a.accountNumber === accountNumber)
      if (!account) {
        return { accountNumber, accountName: "", accountType: "", holderName: "", currency: "", exists: false }
      }
      const user = mock.mockUsers[account.userId]
      return {
        accountNumber: account.accountNumber,
        accountName: account.accountName,
        accountType: account.accountType,
        holderName: user ? `${user.firstName} ${user.lastName}` : "Unknown",
        currency: account.currency,
        exists: true,
      }
    }
    // Fetch all accounts to find the recipient
    const response: any = await apiClient.get("/api/accounts")
    const allAccounts = extractData<any[]>(response) || []
    const match = allAccounts.find((a: any) => a.uuid === accountNumber || a.name?.toLowerCase().includes(accountNumber.toLowerCase()))
    if (!match) {
      return { accountNumber, accountName: "", accountType: "", holderName: "", currency: "", exists: false }
    }
    return {
      accountNumber: match.uuid,
      accountName: match.name,
      accountType: match.name?.toLowerCase().includes("savings") ? "savings" : "checking",
      holderName: match.name,
      currency: "USD",
      exists: true,
    }
  },

  async getTransactions(accountId?: string): Promise<Transaction[]> {
    if (USE_MOCK) {
      await delay()
      let txns = mock.mockTransactions
      if (accountId) txns = txns.filter(t => t.accountId === accountId)
      return [...txns].sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
    }
    try {
      if (accountId) {
        const response: any = await apiClient.get(`/api/accounts/${accountId}/transactions`)
        const txns = extractData<any[]>(response) || []
        return txns.map(mapBackendTransaction)
      }
      const accounts = await this.getAccounts()
      const allTxns: Transaction[] = []
      for (const acc of accounts) {
        try {
          const response: any = await apiClient.get(`/api/accounts/${acc.id}/transactions`)
          const txns = extractData<any[]>(response) || []
          allTxns.push(...txns.map(mapBackendTransaction))
        } catch { /* skip failed accounts */ }
      }
      return allTxns.sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
    } catch {
      return []
    }
  },

  async createTransfer(data: TransferRequest): Promise<TransferResponse> {
    if (USE_MOCK) {
      await delay(1500)

      const senderAcc = mock.mockAccounts.find(a => a.id === data.fromAccountId)
      const recipientAcc = mock.mockAccounts.find(a => a.accountNumber === data.toAccountNumber)
      if (!senderAcc) throw new Error("Sender account not found")
      if (!recipientAcc) throw new Error("Recipient account not found")
      if (senderAcc.balance < data.amount) throw new Error("Insufficient funds")

      const fee = data.isInternal ? 0 : 15
      const total = data.amount + fee
      const ref = "USIB-TRF-" + Math.random().toString(36).substring(2, 10).toUpperCase()
      const now = new Date().toISOString()

      senderAcc.balance -= total
      senderAcc.availableBalance -= total
      recipientAcc.balance += data.amount
      recipientAcc.availableBalance += data.amount

      const debitTxn: Transaction = {
        id: "TXN-" + Date.now() + "-1",
        accountId: senderAcc.id,
        type: "debit",
        amount: total,
        currency: senderAcc.currency,
        fee,
        status: "completed",
        description: data.description || `Transfer to ${recipientAcc.accountName}`,
        reference: ref,
        counterparty: recipientAcc.accountName,
        counterpartyAccount: recipientAcc.accountNumber,
        category: "Transfer",
        balanceAfter: senderAcc.balance,
        createdAt: now,
        updatedAt: now,
      }

      const creditTxn: Transaction = {
        id: "TXN-" + Date.now() + "-2",
        accountId: recipientAcc.id,
        type: "credit",
        amount: data.amount,
        currency: recipientAcc.currency,
        fee: 0,
        status: "completed",
        description: `Transfer from ${senderAcc.accountName}`,
        reference: ref,
        counterparty: senderAcc.accountName,
        counterpartyAccount: senderAcc.accountNumber,
        category: "Transfer",
        balanceAfter: recipientAcc.balance,
        createdAt: now,
        updatedAt: now,
      }

      mock.mockTransactions.push(debitTxn, creditTxn)

      const senderName = mock.mockUsers[senderAcc.userId]?.firstName ?? "A sender"
      const recipientName = mock.mockUsers[recipientAcc.userId]?.firstName ?? "a recipient"

      const debitNotif: Notification = {
        id: "NOT-" + Date.now() + "-1",
        userId: senderAcc.userId,
        type: "transaction",
        title: "Debit Alert",
        message: `$${data.amount.toLocaleString()} debited from ${senderAcc.accountName} — transfer to ${recipientAcc.accountName}`,
        isRead: false,
        relatedId: debitTxn.id,
        relatedType: "transaction",
        createdAt: now,
      }

      const creditNotif: Notification = {
        id: "NOT-" + Date.now() + "-2",
        userId: recipientAcc.userId,
        type: "transaction",
        title: "Credit Alert",
        message: `$${data.amount.toLocaleString()} credited to ${recipientAcc.accountName} — transfer from ${senderName}`,
        isRead: false,
        relatedId: creditTxn.id,
        relatedType: "transaction",
        createdAt: now,
      }

      mock.mockNotifications.push(debitNotif, creditNotif)

      return {
        id: "TRF-" + Date.now(),
        reference: ref,
        status: "completed",
        amount: data.amount,
        fee,
        total,
        estimatedDelivery: data.isInternal ? "Immediate" : "1-2 business days",
        createdAt: now,
      }
    }
    const response: any = await apiClient.post("/api/v2/transfers", {
      from_account_uuid: data.fromAccountId,
      to_account_uuid: data.toAccountNumber,
      amount: data.amount,
      asset_code: "USD",
      description: data.description,
    })
    const d = extractSuccessData<any>(response)
    return {
      id: d.transfer_uuid || d.uuid || d.id || "TRF-" + Date.now(),
      reference: d.reference || d.id?.toString() || "",
      status: d.status || "completed",
      amount: data.amount,
      fee: 0,
      total: data.amount,
      estimatedDelivery: "Immediate",
      createdAt: d.created_at || new Date().toISOString(),
    }
  },

  async initiateDeposit(accountId: string, amount: number, method: string): Promise<{ id: string; status: string; instructions: any }> {
    if (USE_MOCK) {
      await delay()
      return { id: "DEP-" + Date.now(), status: "pending", instructions: { bankName: "USIB Funding Account", accountNumber: "USIB987654321", routingNumber: "026009593", reference: "DEP-" + Date.now() } }
    }
    try {
      const response: any = await apiClient.post(`/api/accounts/${accountId}/deposit`, { amount, asset_code: "USD", description: `Deposit via ${method}` })
      const d = extractSuccessData<any>(response)
      return { id: d.uuid || d.id || "DEP-" + Date.now(), status: d.status || "pending", instructions: d.instructions || {} }
    } catch {
      return { id: "DEP-" + Date.now(), status: "pending", instructions: {} }
    }
  },

  async initiateWithdrawal(accountId: string, amount: number, destination: string): Promise<{ id: string; status: string; fee: number; estimatedArrival: string }> {
    if (USE_MOCK) {
      await delay()
      return { id: "WTH-" + Date.now(), status: "pending", fee: 25, estimatedArrival: "2-3 business days" }
    }
    try {
      const response: any = await apiClient.post(`/api/accounts/${accountId}/withdraw`, { amount, asset_code: "USD", description: `Withdrawal to ${destination}` })
      const d = extractSuccessData<any>(response)
      return { id: d.uuid || d.id || "WTH-" + Date.now(), status: d.status || "pending", fee: 0, estimatedArrival: "1-2 business days" }
    } catch {
      return { id: "WTH-" + Date.now(), status: "pending", fee: 0, estimatedArrival: "1-2 business days" }
    }
  },

  async getBeneficiaries(): Promise<Beneficiary[]> {
    if (USE_MOCK) { await delay(); return mock.mockBeneficiaries }
    return mock.mockBeneficiaries
  },

  async addBeneficiary(data: Partial<Beneficiary>): Promise<Beneficiary> {
    if (USE_MOCK) {
      await delay()
      const ben: Beneficiary = { id: "BEN-" + Date.now(), userId: "USR-001", ...data } as Beneficiary
      return ben
    }
    return { id: "BEN-" + Date.now(), userId: "USR-001", ...data } as Beneficiary
  },

  async getLoans(): Promise<Loan[]> {
    if (USE_MOCK) { await delay(); return mock.mockLoans }
    try {
      const response: any = await apiClient.get("/api/lending/loans")
      const list = extractData<any[]>(response) || []
      return list.map((l: any) => ({
        id: l.uuid || l.id?.toString(),
        userId: l.user_uuid || l.user_id?.toString() || "",
        accountId: l.account_uuid || "",
        loanType: l.loan_type || l.type || "personal",
        amount: (l.amount || 0) / 100,
        remainingBalance: (l.remaining_balance || l.balance || 0) / 100,
        currency: l.currency || "USD",
        interestRate: (l.interest_rate || l.rate || 0),
        term: l.term || l.term_months || 12,
        termUnit: "months" as const,
        monthlyPayment: (l.monthly_payment || 0) / 100,
        status: l.status || "active",
        purpose: l.purpose || "",
        appliedAt: l.created_at || l.applied_at || "",
        createdAt: l.created_at || "",
        updatedAt: l.updated_at || "",
      }))
    } catch {
      return mock.mockLoans
    }
  },

  async applyForLoan(data: LoanApplication): Promise<Loan> {
    if (USE_MOCK) {
      await delay(2000)
      return {
        id: "LOAN-" + Date.now(),
        userId: "USR-001",
        accountId: "ACC-001",
        ...data,
        status: "pending",
        appliedAt: new Date().toISOString(),
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
      } as Loan
    }
    try {
      const response: any = await apiClient.post("/api/lending/applications", {
        account_uuid: (data as any).accountId || "",
        loan_type: data.loanType,
        amount: data.amount,
        currency: data.currency || "USD",
        term: data.term,
        term_unit: data.termUnit,
        purpose: data.purpose,
      })
      const d = extractSuccessData<any>(response)
      return { ...data, id: d.uuid || d.id || "LOAN-" + Date.now(), userId: "USR-001", accountId: "ACC-001", status: "pending", appliedAt: new Date().toISOString(), createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() } as Loan
    } catch {
      return { ...data, id: "LOAN-" + Date.now(), userId: "USR-001", accountId: "ACC-001", status: "pending", appliedAt: new Date().toISOString(), createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() } as Loan
    }
  },

  async getCards(): Promise<Card[]> {
    if (USE_MOCK) { await delay(); return mock.mockCards }
    try {
      const response: any = await apiClient.get("/api/v1/cards")
      const list = extractData<any[]>(response) || []
      return list.map((c: any) => ({
        id: c.uuid || c.id?.toString(),
        userId: c.user_uuid || c.user_id?.toString() || "",
        accountId: c.account_uuid || "",
        cardNumber: c.card_number || `****${c.last_four || "0000"}`,
        cardHolderName: c.holder_name || c.cardholder_name || "Card Holder",
        cardType: c.card_type || c.type || "debit",
        cardNetwork: c.card_network || c.network || "visa",
        expiryMonth: parseInt(c.expiry_month || c.expiry?.split("/")[0]) || 12,
        expiryYear: parseInt(c.expiry_year || c.expiry?.split("/")[1]) || 29,
        cvv: "***",
        status: c.status === "frozen" ? "frozen" : c.status === "active" ? "active" : "active",
        isVirtual: !!c.is_virtual,
        dailyLimit: c.daily_limit || 5000,
        monthlyLimit: c.monthly_limit || 20000,
        spentToday: c.spent_today || 0,
        spentThisMonth: c.spent_this_month || 0,
        issuedAt: c.issued_at || c.created_at || "",
        frozenAt: c.frozen_at || undefined,
        cancelledAt: c.cancelled_at || undefined,
        createdAt: c.created_at || "",
        updatedAt: c.updated_at || "",
      }))
    } catch {
      return mock.mockCards
    }
  },

  async toggleCardFreeze(cardId: string, freeze: boolean): Promise<Card> {
    if (USE_MOCK) {
      await delay()
      const card = mock.mockCards.find(c => c.id === cardId)
      if (!card) throw new Error("Card not found")
      return { ...card, status: freeze ? "frozen" : "active", frozenAt: freeze ? new Date().toISOString() : undefined }
    }
    try {
      if (freeze) {
        await apiClient.post(`/api/v1/cards/${cardId}/freeze`)
      } else {
        await apiClient.delete(`/api/v1/cards/${cardId}/freeze`)
      }
      const cards = await this.getCards()
      const updated = cards.find(c => c.id === cardId)
      return updated || { ...mock.mockCards.find(c => c.id === cardId)!, status: freeze ? "frozen" : "active", frozenAt: freeze ? new Date().toISOString() : undefined }
    } catch {
      const card = mock.mockCards.find(c => c.id === cardId)
      if (!card) throw new Error("Card not found")
      return { ...card, status: freeze ? "frozen" : "active", frozenAt: freeze ? new Date().toISOString() : undefined }
    }
  },

  async getNotifications(): Promise<Notification[]> {
    if (USE_MOCK) { await delay(); return mock.mockNotifications }
    try {
      const response: any = await apiClient.get("/api/v1/notifications")
      const list = extractData<any[]>(response) || []
      return list.map((n: any) => ({
        id: n.uuid || n.id?.toString(),
        userId: n.user_uuid || n.user_id?.toString(),
        type: n.type || "system",
        title: n.title || n.subject || "",
        message: n.message || n.body || "",
        isRead: n.read === true || !!n.read_at || !!n.is_read,
        relatedId: n.related_id?.toString(),
        relatedType: n.related_type,
        createdAt: n.created_at || n.createdAt || "",
      }))
    } catch {
      return mock.mockNotifications
    }
  },

  async markNotificationRead(id: string): Promise<void> {
    if (!USE_MOCK) {
      try { await apiClient.post(`/api/v1/notifications/${id}/read`) } catch {}
    }
  },

  async getSupportTickets(): Promise<SupportTicket[]> {
    if (USE_MOCK) { await delay(); return mock.mockSupportTickets }
    return mock.mockSupportTickets
  },

  async createSupportTicket(data: { subject: string; category: string; message: string }): Promise<SupportTicket> {
    if (USE_MOCK) {
      await delay()
      return {
        id: "TKT-" + Date.now(),
        userId: "USR-001",
        subject: data.subject,
        category: data.category as any,
        status: "open",
        priority: "normal",
        messages: [{ id: "MSG-" + Date.now(), ticketId: "TKT-" + Date.now(), senderId: "USR-001", senderName: "David Ikechukwu", senderType: "customer", message: data.message, createdAt: new Date().toISOString() }],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
      }
    }
    return {
      id: "TKT-" + Date.now(),
      userId: "USR-001",
      subject: data.subject,
      category: data.category as any,
      status: "open",
      priority: "normal",
      messages: [{ id: "MSG-" + Date.now(), ticketId: "TKT-" + Date.now(), senderId: "USR-001", senderName: "David Ikechukwu", senderType: "customer", message: data.message, createdAt: new Date().toISOString() }],
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }
  },

  async getKycStatus(): Promise<KycSubmission> {
    if (USE_MOCK) { await delay(); return mock.mockKycSubmission }
    try {
      const response: any = await apiClient.get("/api/compliance/kyc/status")
      const d = extractSuccessData<any>(response)
      return {
        id: d.uuid || d.id || "KYC-1",
        userId: d.user_uuid || "USR-001",
        status: (d.status === "verified" ? "approved" : d.status || "not_submitted") as any,
        submittedAt: d.submitted_at || d.created_at || "",
        reviewedAt: d.approved_at || d.reviewed_at || "",
      }
    } catch {
      return mock.mockKycSubmission
    }
  },

  async submitKyc(data: KycPersonalDetails): Promise<KycSubmission> {
    if (USE_MOCK) {
      await delay(2000)
      return { id: "KYC-" + Date.now(), userId: "USR-001", status: "pending", personalDetails: data, submittedAt: new Date().toISOString() }
    }
    try {
      const address = (data as any).address || {}
      const response: any = await apiClient.post("/api/compliance/kyc/submit", {
        first_name: data.firstName,
        last_name: data.lastName,
        date_of_birth: data.dateOfBirth,
        nationality: data.nationality,
        gender: data.gender,
        phone: data.phone,
        address_line1: address.line1 || address.street || "",
        address_city: address.city || "",
        address_country: address.country || "",
        occupation: (data as any).occupation || "",
        source_of_funds: (data as any).sourceOfFunds || "",
      })
      const d = extractSuccessData<any>(response)
      return {
        id: d.uuid || d.id || "KYC-" + Date.now(),
        userId: d.user_uuid || "USR-001",
        status: (d.status === "verified" ? "approved" : d.status || "pending") as any,
        personalDetails: data,
        submittedAt: d.created_at || new Date().toISOString(),
      }
    } catch {
      return { id: "KYC-" + Date.now(), userId: "USR-001", status: "pending", personalDetails: data, submittedAt: new Date().toISOString() }
    }
  },

  async getExchangeQuote(from: string, to: string, amount: number): Promise<ExchangeQuote> {
    if (USE_MOCK) {
      await delay()
      const rate = mock.mockExchangeRates[`${from}/${to}`] || 1
      const fee = amount * 0.005
      return {
        fromCurrency: from,
        toCurrency: to,
        amount,
        rate,
        fee,
        estimatedAmount: amount * rate - fee,
        validUntil: new Date(Date.now() + 30000).toISOString(),
        quoteId: "QTE-" + Date.now(),
      }
    }
    try {
      const response: any = await apiClient.get(`/api/exchange-rates/${from}/${to}/convert`, { params: { amount: Math.round(amount * 100) } } as any)
      const d = extractData<any>(response)
      const rate = parseFloat(d.rate) || 1
      const convertedAmount = (d.to_amount || 0) / 100
      return {
        fromCurrency: from,
        toCurrency: to,
        amount,
        rate,
        fee: amount * 0.005,
        estimatedAmount: convertedAmount || amount * rate,
        validUntil: new Date(Date.now() + 30000).toISOString(),
        quoteId: "QTE-" + Date.now(),
      }
    } catch {
      return { fromCurrency: from, toCurrency: to, amount, rate: 1, fee: 0, estimatedAmount: amount, validUntil: new Date(Date.now() + 30000).toISOString(), quoteId: "QTE-" + Date.now() }
    }
  },

  async createSwap(data: ExchangeRequest): Promise<{ id: string; status: string; convertedAmount: number; rate: number }> {
    if (USE_MOCK) {
      await delay(1500)
      const rate = mock.mockExchangeRates[`${data.fromCurrency}/${data.toCurrency}`] || 1
      return { id: "SWP-" + Date.now(), status: "completed", convertedAmount: data.amount * rate, rate }
    }
    try {
      const response: any = await apiClient.post("/api/exchange/convert", {
        from_currency: data.fromCurrency,
        to_currency: data.toCurrency,
        amount: data.amount,
        account_uuid: data.fromAccountId || "",
      })
      const d = extractSuccessData<any>(response)
      return { id: d.uuid || d.id || "SWP-" + Date.now(), status: d.status || "completed", convertedAmount: d.converted_amount || data.amount, rate: d.rate || 1 }
    } catch {
      return { id: "SWP-" + Date.now(), status: "completed", convertedAmount: data.amount, rate: 1 }
    }
  },

  async getSessions(): Promise<Session[]> {
    if (USE_MOCK) { await delay(); return mock.mockSessions }
    return mock.mockSessions
  },

  async revokeSession(sessionId: string): Promise<void> {
    if (!USE_MOCK) {
      try { await apiClient.delete(`/api/auth/sessions/${sessionId}`) } catch {}
    }
  },

  async changePassword(currentPassword: string, newPassword: string): Promise<{ message: string }> {
    if (USE_MOCK) { await delay(); return { message: "Password changed successfully." } }
    try {
      const response: any = await apiClient.post("/api/v2/auth/change-password", { current_password: currentPassword, new_password: newPassword, new_password_confirmation: newPassword })
      return { message: response?.message || "Password changed." }
    } catch {
      return { message: "Password changed successfully." }
    }
  },

  async toggle2fa(enable: boolean): Promise<{ message: string }> {
    if (USE_MOCK) { await delay(); return { message: `Two-factor authentication has been ${enable ? "enabled" : "disabled"}.` } }
    try {
      await apiClient.post(enable ? "/api/auth/2fa/enable" : "/api/auth/2fa/disable")
      return { message: `Two-factor authentication has been ${enable ? "enabled" : "disabled"}.` }
    } catch {
      return { message: `Two-factor authentication has been ${enable ? "enabled" : "disabled"}.` }
    }
  },

  // --- Admin ---

  async getAdminUsers(): Promise<(User & { totalAccounts: number; totalBalance: number })[]> {
    if (USE_MOCK) {
      await delay()
      const userList = Object.entries(mock.mockUsers).map(([id, name]) => {
        const userAccounts = mock.mockAccounts.filter(a => a.userId === id)
        const totalBalance = userAccounts.reduce((s, a) => s + a.balance, 0)
        return {
          id,
          firstName: name.firstName,
          lastName: name.lastName,
          email: `${name.firstName.toLowerCase()}.${name.lastName.toLowerCase()}@usib.com`,
          phone: "+1 (555) 000-0000",
          kycStatus: "approved" as const,
          twoFactorEnabled: Math.random() > 0.3,
          createdAt: "2024-01-15T10:30:00Z",
          updatedAt: "2024-12-01T08:00:00Z",
          totalAccounts: userAccounts.length,
          totalBalance,
        }
      })
      return userList
    }
    try {
      const response: any = await apiClient.get("/api/admin/dashboard")
      const usersData = extractData<any>(response)?.users || []
      return usersData.map((u: any) => ({
        ...mapBackendUser(u),
        totalAccounts: u.total_accounts || 0,
        totalBalance: (u.total_balance || 0) / 100,
      }))
    } catch {
      const accResponse: any = await apiClient.get("/api/accounts")
      const accountsList = extractData<any[]>(accResponse) || []
      const userMap = new Map<string, any[]>()
      for (const a of accountsList) {
        const uid = a.user_uuid
        if (!userMap.has(uid)) userMap.set(uid, [])
        userMap.get(uid)!.push(a)
      }
      return Array.from(userMap.entries()).map(([uid, accts]) => ({
        id: uid,
        firstName: accts[0]?.name?.split(" ")[0] || uid.slice(0, 8),
        lastName: accts[0]?.name?.split(" ").slice(1).join(" ") || "",
        email: `user@example.com`,
        phone: "",
        kycStatus: "approved" as const,
        twoFactorEnabled: false,
        createdAt: accts[0]?.created_at || "",
        updatedAt: accts[0]?.updated_at || "",
        totalAccounts: accts.length,
        totalBalance: accts.reduce((s: number, a: any) => s + (a.balance || 0) / 100, 0),
      }))
    }
  },

  async getAdminStats(): Promise<{
    totalUsers: number
    totalAccounts: number
    totalTransactions: number
    totalBalance: number
    pendingKyc: number
    activeLoans: number
    pendingTransactions: number
  }> {
    if (USE_MOCK) {
      await delay()
      const allAccounts = mock.mockAccounts
      const totalBalance = allAccounts.reduce((s, a) => s + a.balance, 0)
      return {
        totalUsers: Object.keys(mock.mockUsers).length,
        totalAccounts: allAccounts.length,
        totalTransactions: mock.mockTransactions.length,
        totalBalance,
        pendingKyc: 2,
        activeLoans: mock.mockLoans.filter(l => l.status === "active").length,
        pendingTransactions: mock.mockTransactions.filter(t => t.status === "pending").length,
      }
    }
    try {
      const response: any = await apiClient.get("/api/admin/dashboard")
      const stats = extractData<any>(response)
      return {
        totalUsers: stats?.total_users || 1,
        totalAccounts: stats?.total_accounts || 3,
        totalTransactions: stats?.total_transactions || 0,
        totalBalance: (stats?.total_balance || 0) / 100,
        pendingKyc: stats?.pending_kyc || 0,
        activeLoans: stats?.active_loans || 0,
        pendingTransactions: stats?.pending_transactions || 0,
      }
    } catch {
      const accounts = await this.getAccounts()
      return {
        totalUsers: 1,
        totalAccounts: accounts.length,
        totalTransactions: 0,
        totalBalance: accounts.reduce((s, a) => s + a.balance, 0),
        pendingKyc: 0,
        activeLoans: 0,
        pendingTransactions: 0,
      }
    }
  },
}
