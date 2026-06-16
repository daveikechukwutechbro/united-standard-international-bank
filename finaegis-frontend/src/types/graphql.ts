export interface PaginatedResult<T> {
  data: T[]
  paginatorInfo: {
    count: number
    currentPage: number
    firstItem: number
    hasMorePages: boolean
    lastItem: number
    lastPage: number
    perPage: number
    total: number
  }
}

export interface Account {
  id: string
  uuid: string
  name: string
  balance: number
  frozen: boolean
  user_uuid: string
  team_uuid: string
  created_at: string
  updated_at: string
}

export interface PaymentTransaction {
  id: string
  aggregate_uuid: string
  account_uuid: string
  type: string
  status: string
  amount: number
  currency: string
  reference: string
  external_reference: string
  payment_method: string
  initiated_at: string
  completed_at: string
  created_at: string
}

export interface UserProfile {
  id: string
  user_id: number
  email: string
  first_name: string
  last_name: string
  phone_number: string
  country: string
  city: string
  status: string
  is_verified: boolean
  created_at: string
}

export interface BankAccountModel {
  id: string
  user_uuid: string
  bank_code: string
  account_number: string
  iban: string
  swift: string
  currency: string
  account_type: string
  status: string
  created_at: string
}

export interface BankTransferModel {
  id: string
  from_account_id: string
  to_account_id: string
  amount: string
  currency: string
  status: string
  reference: string
  created_at: string
}

export interface ExchangeOrder {
  id: string
  order_id: string
  account_id: string
  type: string
  order_type: string
  base_currency: string
  quote_currency: string
  amount: number
  filled_amount: number
  price: number
  status: string
  created_at: string
}

export interface Trade {
  id: string
  trade_id: string
  buy_order_id: string
  sell_order_id: string
  base_currency: string
  quote_currency: string
  price: number
  amount: number
  value: number
  created_at: string
}

export interface OrderBook {
  id: string
  order_book_id: string
  base_currency: string
  quote_currency: string
  best_bid: number
  best_ask: number
  last_price: number
  volume_24h: number
  high_24h: number
  low_24h: number
}

export interface MultiSigWallet {
  id: string
  user_id: number
  name: string
  address: string
  chain: string
  required_signatures: number
  total_signers: number
  status: string
  created_at: string
}

export interface LoanApplication {
  id: string
  borrower_id: string
  requested_amount: number
  term_months: number
  purpose: string
  status: string
  credit_score: number
  risk_rating: string
  interest_rate: number
  approved_amount: number
  created_at: string
}

export interface KycVerification {
  id: string
  verification_number: string
  type: string
  status: string
  provider: string
  confidence_score: number
  document_type: string
  risk_level: string
  created_at: string
}

export interface ComplianceAlert {
  id: string
  alert_id: string
  type: string
  severity: string
  status: string
  title: string
  description: string
  risk_score: number
  detected_at: string
  created_at: string
}

export interface ComplianceCase {
  id: string
  case_id: string
  case_number: string
  title: string
  type: string
  priority: string
  status: string
  total_risk_score: number
  created_at: string
}

export interface StablecoinReserve {
  id: string
  reserve_id: string
  pool_id: string
  stablecoin_code: string
  asset_code: string
  amount: number
  value_usd: number
  allocation_percentage: number
  custodian_name: string
  status: string
}

export interface AssetAllocation {
  id: string
  portfolio_id: string
  asset_class: string
  target_weight: number
  current_weight: number
  drift: number
  current_amount: number
  created_at: string
}

export interface BasketPortfolio {
  id: string
  code: string
  name: string
  description: string
  type: string
  rebalance_frequency: string
  is_active: boolean
  last_rebalanced_at: string
}

export interface VirtualCard {
  id: string
  card_token: string
  cardholder_name: string
  last_four: string
  network: string
  status: string
  label: string
  created_at: string
}

export interface CardTransaction {
  id: string
  card_id: string
  merchant_name: string
  amount_cents: number
  currency: string
  status: string
  transacted_at: string
}

export interface BridgeTransaction {
  id: string
  source_chain: string
  dest_chain: string
  token: string
  amount: number
  provider: string
  status: string
  source_tx_hash: string
  dest_tx_hash: string
  created_at: string
}

export interface DeFiPosition {
  id: string
  protocol: string
  type: string
  status: string
  chain: string
  asset: string
  amount: number
  value_usd: number
  apy: number
  health_factor: number
  created_at: string
}

export interface FraudCase {
  id: string
  uuid: string
  case_number: string
  status: string
  severity: string
  type: string
  amount: number
  risk_score: number
  description: string
  created_at: string
}

export interface AiConversationResult {
  conversation_id: string
  response: string
  tokens_used: number
}

export interface RewardProfile {
  id: string
  xp: number
  level: number
  xp_for_next: number
  xp_progress: number
  current_streak: number
  points_balance: number
  quests_completed: number
}

export interface Agent {
  id: string
  agent_id: string
  name: string
  status: string
  type: string
  capabilities: string[]
  relay_score: number
}

export interface X402Payment {
  id: string
  payer_address: string
  pay_to_address: string
  amount: string
  network: string
  status: string
  transaction_hash: string
  created_at: string
}

export interface LedgerAccount {
  code: string
  name: string
  type: string
  parent_code: string
  currency: string
  is_active: boolean
}

export interface TrialBalanceEntry {
  account_code: string
  debit: string
  credit: string
  balance: string
}

export interface CgoInvestment {
  id: string
  uuid: string
  amount: number
  status: string
  tier: string
  share_price: number
  shares_purchased: number
  created_at: string
}

export interface CustodianAccount {
  id: string
  account_uuid: string
  custodian_name: string
  status: string
  is_primary: boolean
  last_known_balance: number
}

export interface SmartAccount {
  id: string
  owner_address: string
  account_address: string
  network: string
  deployed: boolean
  nonce: number
}

export interface Poll {
  id: string
  uuid: string
  title: string
  description: string
  type: string
  status: string
  start_date: string
  end_date: string
  created_at: string
}

export interface Merchant {
  id: string
  public_id: string
  display_name: string
  status: string
  accepted_assets: string
  accepted_networks: string
}

export interface Product {
  id: string
  name: string
  description: string
  category: string
  type: string
  status: string
  features: string[]
}

export interface RegulatoryReport {
  id: string
  report_type: string
  jurisdiction: string
  status: string
  due_date: string
  created_at: string
}

export interface Partner {
  id: string
  institution_name: string
  legal_name: string
  institution_type: string
  country: string
  status: string
  tier: string
}

export interface IlpQuote {
  send_asset: string
  send_amount: string
  receive_asset: string
  receive_amount: string
  exchange_rate: number
  fee: string
  expires_at: string
}

export interface MfiGroup {
  id: string
  name: string
  status: string
  meeting_frequency: string
  created_at: string
}
