import type {
  User, Account, Transaction, Beneficiary, Loan, Card,
  Notification, SupportTicket, KycSubmission, ExchangeQuote,
  DashboardSummary, TransferRequest, TransferResponse,
  LoanApplication, ExchangeRequest, Session, KycPersonalDetails,
  RecipientLookupResult
} from "@/lib/types"
import { apiClient } from "@/lib/api-client"
import * as mock from "@/lib/mock-data"

const USE_MOCK = true

function delay(ms = 800) {
  return new Promise((resolve) => setTimeout(resolve, ms))
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
    return apiClient.post("/api/auth/login", { email, password })
  },

  async register(data: { firstName: string; lastName: string; email: string; phone: string; password: string }): Promise<{ user: User; token: string }> {
    if (USE_MOCK) {
      await delay()
      return {
        user: { ...mock.mockUser, firstName: data.firstName, lastName: data.lastName, email: data.email, phone: data.phone, kycStatus: "not_submitted" },
        token: "mock-jwt-token-2024",
      }
    }
    return apiClient.post("/api/auth/register", data)
  },

  async verifyOtp(email: string, otp: string): Promise<{ verified: boolean; token?: string }> {
    if (USE_MOCK) {
      await delay()
      if (otp === "123456") return { verified: true, token: "mock-otp-token" }
      throw new Error("Invalid OTP code")
    }
    return apiClient.post("/api/auth/verify-otp", { email, otp })
  },

  async forgotPassword(email: string): Promise<{ message: string }> {
    if (USE_MOCK) {
      await delay()
      return { message: "If an account exists with this email, a reset link has been sent." }
    }
    return apiClient.post("/api/auth/forgot-password", { email })
  },

  async resetPassword(token: string, password: string): Promise<{ message: string }> {
    if (USE_MOCK) {
      await delay()
      return { message: "Password has been reset successfully." }
    }
    return apiClient.post("/api/auth/reset-password", { token, password })
  },

  async logout(): Promise<void> {
    if (!USE_MOCK) {
      await apiClient.post("/api/auth/logout")
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
    return apiClient.get("/api/dashboard/summary")
  },

  async getAccounts(): Promise<Account[]> {
    if (USE_MOCK) { await delay(); return mock.mockAccounts }
    return apiClient.get("/api/accounts")
  },

  async getAccount(id: string): Promise<Account> {
    if (USE_MOCK) {
      await delay()
      const account = mock.mockAccounts.find(a => a.id === id)
      if (!account) throw new Error("Account not found")
      return account
    }
    return apiClient.get(`/api/accounts/${id}`)
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
    return apiClient.get(`/api/accounts/lookup/${accountNumber}`)
  },

  async getTransactions(accountId?: string): Promise<Transaction[]> {
    if (USE_MOCK) {
      await delay()
      let txns = mock.mockTransactions
      if (accountId) txns = txns.filter(t => t.accountId === accountId)
      return [...txns].sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
    }
    const params = accountId ? { accountId } : undefined
    return apiClient.get("/api/transactions", { params } as any)
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
    return apiClient.post("/api/transfers", data)
  },

  async initiateDeposit(accountId: string, amount: number, method: string): Promise<{ id: string; status: string; instructions: any }> {
    if (USE_MOCK) {
      await delay()
      return { id: "DEP-" + Date.now(), status: "pending", instructions: { bankName: "USIB Funding Account", accountNumber: "USIB987654321", routingNumber: "026009593", reference: "DEP-" + Date.now() } }
    }
    return apiClient.post("/api/deposits", { accountId, amount, method })
  },

  async initiateWithdrawal(accountId: string, amount: number, destination: string): Promise<{ id: string; status: string; fee: number; estimatedArrival: string }> {
    if (USE_MOCK) {
      await delay()
      return { id: "WTH-" + Date.now(), status: "pending", fee: 25, estimatedArrival: "2-3 business days" }
    }
    return apiClient.post("/api/withdrawals", { accountId, amount, destination })
  },

  async getBeneficiaries(): Promise<Beneficiary[]> {
    if (USE_MOCK) { await delay(); return mock.mockBeneficiaries }
    return apiClient.get("/api/beneficiaries")
  },

  async addBeneficiary(data: Partial<Beneficiary>): Promise<Beneficiary> {
    if (USE_MOCK) {
      await delay()
      const ben: Beneficiary = { id: "BEN-" + Date.now(), userId: "USR-001", ...data } as Beneficiary
      return ben
    }
    return apiClient.post("/api/beneficiaries", data)
  },

  async getLoans(): Promise<Loan[]> {
    if (USE_MOCK) { await delay(); return mock.mockLoans }
    return apiClient.get("/api/loans")
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
    return apiClient.post("/api/loans", data)
  },

  async getCards(): Promise<Card[]> {
    if (USE_MOCK) { await delay(); return mock.mockCards }
    return apiClient.get("/api/cards")
  },

  async toggleCardFreeze(cardId: string, freeze: boolean): Promise<Card> {
    if (USE_MOCK) {
      await delay()
      const card = mock.mockCards.find(c => c.id === cardId)
      if (!card) throw new Error("Card not found")
      return { ...card, status: freeze ? "frozen" : "active", frozenAt: freeze ? new Date().toISOString() : undefined }
    }
    return apiClient.post(`/api/cards/${cardId}/${freeze ? "freeze" : "unfreeze"}`)
  },

  async getNotifications(): Promise<Notification[]> {
    if (USE_MOCK) { await delay(); return mock.mockNotifications }
    return apiClient.get("/api/notifications")
  },

  async markNotificationRead(id: string): Promise<void> {
    if (!USE_MOCK) await apiClient.patch(`/api/notifications/${id}/read`)
  },

  async getSupportTickets(): Promise<SupportTicket[]> {
    if (USE_MOCK) { await delay(); return mock.mockSupportTickets }
    return apiClient.get("/api/support/tickets")
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
    return apiClient.post("/api/support/tickets", data)
  },

  async getKycStatus(): Promise<KycSubmission> {
    if (USE_MOCK) { await delay(); return mock.mockKycSubmission }
    return apiClient.get("/api/kyc/status")
  },

  async submitKyc(data: KycPersonalDetails): Promise<KycSubmission> {
    if (USE_MOCK) {
      await delay(2000)
      return { id: "KYC-" + Date.now(), userId: "USR-001", status: "pending", personalDetails: data, submittedAt: new Date().toISOString() }
    }
    return apiClient.post("/api/kyc/submit", data)
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
    return apiClient.get("/api/exchange/quote", { params: { from, to, amount } } as any)
  },

  async createSwap(data: ExchangeRequest): Promise<{ id: string; status: string; convertedAmount: number; rate: number }> {
    if (USE_MOCK) {
      await delay(1500)
      const rate = mock.mockExchangeRates[`${data.fromCurrency}/${data.toCurrency}`] || 1
      return { id: "SWP-" + Date.now(), status: "completed", convertedAmount: data.amount * rate, rate }
    }
    return apiClient.post("/api/exchange/swap", data)
  },

  async getSessions(): Promise<Session[]> {
    if (USE_MOCK) { await delay(); return mock.mockSessions }
    return apiClient.get("/api/auth/sessions")
  },

  async revokeSession(sessionId: string): Promise<void> {
    if (!USE_MOCK) await apiClient.delete(`/api/auth/sessions/${sessionId}`)
  },

  async changePassword(currentPassword: string, newPassword: string): Promise<{ message: string }> {
    if (USE_MOCK) { await delay(); return { message: "Password changed successfully." } }
    return apiClient.post("/api/auth/change-password", { currentPassword, newPassword })
  },

  async toggle2fa(enable: boolean): Promise<{ message: string }> {
    if (USE_MOCK) { await delay(); return { message: `Two-factor authentication has been ${enable ? "enabled" : "disabled"}.` } }
    return apiClient.post("/api/auth/2fa/toggle", { enable })
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
    return apiClient.get("/api/admin/users")
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
    return apiClient.get("/api/admin/stats")
  },
}
