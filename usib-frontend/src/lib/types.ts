export interface User {
  id: string
  firstName: string
  lastName: string
  email: string
  phone: string
  dateOfBirth?: string
  address?: Address
  kycStatus: KycStatus
  twoFactorEnabled: boolean
  createdAt: string
  updatedAt: string
}

export interface Address {
  street: string
  city: string
  state: string
  zipCode: string
  country: string
}

export interface Account {
  id: string
  userId: string
  accountNumber: string
  accountType: "checking" | "savings" | "fixed_deposit" | "credit" | "loan"
  accountName: string
  currency: string
  balance: number
  availableBalance: number
  ledgerBalance: number
  status: "active" | "frozen" | "closed" | "pending"
  interestRate?: number
  openedAt: string
  closedAt?: string
  createdAt: string
  updatedAt: string
}

export interface Transaction {
  id: string
  accountId: string
  type: "credit" | "debit" | "transfer" | "deposit" | "withdrawal" | "payment" | "fee" | "interest" | "exchange"
  amount: number
  currency: string
  fee?: number
  status: "pending" | "completed" | "failed" | "reversed" | "cancelled"
  description: string
  reference: string
  counterparty?: string
  counterpartyAccount?: string
  category?: string
  balanceAfter?: number
  createdAt: string
  updatedAt: string
}

export interface Beneficiary {
  id: string
  userId: string
  name: string
  accountNumber: string
  bankName: string
  bankCode?: string
  currency: string
  email?: string
  phone?: string
  isFavorite: boolean
  maxTransferLimit?: number
  createdAt: string
  updatedAt: string
}

export interface TransferRequest {
  fromAccountId: string
  toAccountNumber: string
  toBankCode?: string
  amount: number
  currency?: string
  description?: string
  scheduledDate?: string
  beneficiaryId?: string
  isInternal: boolean
}

export interface TransferResponse {
  id: string
  reference: string
  status: "pending" | "completed" | "failed" | "cancelled"
  amount: number
  fee: number
  total: number
  estimatedDelivery: string
  createdAt: string
}

export interface Loan {
  id: string
  userId: string
  accountId: string
  loanType: "personal" | "business" | "mortgage" | "auto" | "education"
  amount: number
  currency: string
  interestRate: number
  term: number
  termUnit: "months" | "years"
  status: "draft" | "pending" | "approved" | "rejected" | "funded" | "active" | "closed" | "defaulted"
  purpose?: string
  collateral?: string
  monthlyPayment?: number
  totalRepayment?: number
  amountPaid?: number
  remainingBalance?: number
  nextPaymentDate?: string
  appliedAt?: string
  approvedAt?: string
  fundedAt?: string
  closedAt?: string
  createdAt: string
  updatedAt: string
}

export interface LoanApplication {
  loanType: Loan["loanType"]
  amount: number
  currency: string
  term: number
  termUnit: "months" | "years"
  purpose: string
  monthlyIncome?: number
  employmentStatus?: string
  employerName?: string
}

export interface Card {
  id: string
  userId: string
  accountId: string
  cardNumber: string
  cardHolderName: string
  expiryMonth: number
  expiryYear: number
  cvv: string
  cardType: "debit" | "credit" | "virtual"
  cardNetwork: "visa" | "mastercard" | "amex"
  status: "active" | "frozen" | "cancelled" | "expired"
  isVirtual: boolean
  dailyLimit: number
  monthlyLimit: number
  spentToday: number
  spentThisMonth: number
  issuedAt: string
  frozenAt?: string
  cancelledAt?: string
  createdAt: string
  updatedAt: string
}

export interface Notification {
  id: string
  userId: string
  type: "transaction" | "security" | "promotion" | "system" | "kyc" | "loan" | "card"
  title: string
  message: string
  isRead: boolean
  relatedId?: string
  relatedType?: string
  createdAt: string
  readAt?: string
}

export interface SupportTicket {
  id: string
  userId: string
  subject: string
  category: "account" | "card" | "loan" | "transfer" | "technical" | "fraud" | "other"
  status: "open" | "in_progress" | "waiting_on_customer" | "resolved" | "closed"
  priority: "low" | "normal" | "high" | "urgent"
  messages: TicketMessage[]
  createdAt: string
  updatedAt: string
  resolvedAt?: string
  closedAt?: string
}

export interface TicketMessage {
  id: string
  ticketId: string
  senderId: string
  senderName: string
  senderType: "customer" | "agent"
  message: string
  attachments?: string[]
  createdAt: string
}

export type KycStatus = "not_submitted" | "pending" | "under_review" | "approved" | "rejected"

export interface KycSubmission {
  id: string
  userId: string
  status: KycStatus
  personalDetails?: KycPersonalDetails
  documents?: KycDocument[]
  addressVerified?: boolean
  faceVerified?: boolean
  submittedAt?: string
  reviewedAt?: string
  rejectionReason?: string
}

export interface KycPersonalDetails {
  firstName: string
  lastName: string
  dateOfBirth: string
  nationality: string
  gender: string
  phone: string
  address: Address
  occupation?: string
  sourceOfFunds?: string
}

export interface KycDocument {
  id: string
  type: "passport" | "drivers_license" | "national_id" | "utility_bill" | "bank_statement"
  status: "pending" | "approved" | "rejected"
  fileName: string
  uploadedAt: string
  verifiedAt?: string
}

export interface ExchangeQuote {
  fromCurrency: string
  toCurrency: string
  amount: number
  rate: number
  fee: number
  estimatedAmount: number
  validUntil: string
  quoteId: string
}

export interface ExchangeRequest {
  fromAccountId: string
  toAccountId: string
  fromCurrency: string
  toCurrency: string
  amount: number
  quoteId?: string
}

export interface SavingsGoal {
  id: string
  userId: string
  accountId: string
  name: string
  targetAmount: number
  currentAmount: number
  currency: string
  targetDate?: string
  status: "active" | "completed" | "cancelled"
  autoDeposit?: boolean
  depositFrequency?: "daily" | "weekly" | "monthly"
  depositAmount?: number
  createdAt: string
}

export interface RecipientLookupResult {
  accountNumber: string
  accountName: string
  accountType: string
  holderName: string
  currency: string
  exists: boolean
}

export interface DashboardSummary {
  totalBalance: number
  availableBalance: number
  pendingTransactions: number
  activeLoans: number
  totalSavings: number
  recentTransactions: Transaction[]
  accounts: Account[]
  currencyBalances: { currency: string; balance: number; change: number }[]
  spendingByCategory: { category: string; amount: number; percentage: number }[]
}

export interface Session {
  id: string
  deviceName: string
  deviceType: string
  browser: string
  ipAddress: string
  location: string
  isCurrent: boolean
  lastActive: string
  createdAt: string
}
