import { gql } from "@apollo/client"

export const GET_USER_PROFILE = gql`
  query UserProfile {
    userProfile {
      id
      user_id
      email
      first_name
      last_name
      phone_number
      country
      city
      status
      is_verified
      created_at
      updated_at
    }
  }
`

export const GET_ACCOUNTS = gql`
  query Accounts($page: Int, $first: Int) {
    accounts(page: $page, first: $first) {
      data {
        id
        uuid
        name
        balance
        frozen
        user_uuid
        created_at
        updated_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_ACCOUNT = gql`
  query Account($id: ID!) {
    account(id: $id) {
      id
      uuid
      name
      balance
      frozen
      user_uuid
      created_at
      updated_at
    }
  }
`

export const GET_PAYMENTS = gql`
  query Payments($page: Int, $first: Int, $status: String) {
    payments(page: $page, first: $first, status: $status) {
      data {
        id
        aggregate_uuid
        account_uuid
        type
        status
        amount
        currency
        reference
        payment_method
        initiated_at
        completed_at
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_BANK_ACCOUNTS = gql`
  query BankAccounts($page: Int, $first: Int) {
    bankAccounts(page: $page, first: $first) {
      data {
        id
        bank_code
        account_number
        iban
        swift
        currency
        account_type
        status
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_BANK_TRANSFERS = gql`
  query BankTransfers($page: Int, $first: Int) {
    bankTransfers(page: $page, first: $first) {
      id
      from_account_id
      to_account_id
      amount
      currency
      status
      reference
      created_at
    }
  }
`

export const GET_ORDERS = gql`
  query Orders($page: Int, $first: Int, $status: String) {
    orders(page: $page, first: $first, status: $status) {
      data {
        id
        order_id
        type
        order_type
        base_currency
        quote_currency
        amount
        filled_amount
        price
        average_price
        status
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_TRADES = gql`
  query Trades($page: Int, $first: Int) {
    trades(page: $page, first: $first) {
      data {
        id
        trade_id
        base_currency
        quote_currency
        price
        amount
        value
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_ORDER_BOOKS = gql`
  query OrderBooks($page: Int, $first: Int) {
    orderBooks(page: $page, first: $first) {
      data {
        id
        order_book_id
        base_currency
        quote_currency
        best_bid
        best_ask
        last_price
        volume_24h
        high_24h
        low_24h
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_WALLETS = gql`
  query Wallets($page: Int, $first: Int) {
    wallets(page: $page, first: $first) {
      data {
        id
        user_id
        name
        address
        chain
        required_signatures
        total_signers
        status
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_LOAN_APPLICATIONS = gql`
  query LoanApplications($page: Int, $first: Int, $status: String) {
    loanApplications(page: $page, first: $first, status: $status) {
      data {
        id
        borrower_id
        requested_amount
        term_months
        purpose
        status
        credit_score
        risk_rating
        interest_rate
        approved_amount
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_KYC_VERIFICATIONS = gql`
  query KycVerifications($page: Int, $first: Int, $status: String) {
    kycVerifications(page: $page, first: $first, status: $status) {
      data {
        id
        verification_number
        type
        status
        provider
        confidence_score
        document_type
        risk_level
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_COMPLIANCE_ALERTS = gql`
  query ComplianceAlerts($page: Int, $first: Int, $severity: String) {
    complianceAlerts(page: $page, first: $first, severity: $severity) {
      data {
        id
        alert_id
        type
        severity
        status
        title
        description
        risk_score
        detected_at
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_COMPLIANCE_CASES = gql`
  query ComplianceCases($page: Int, $first: Int, $status: String) {
    complianceCases(page: $page, first: $first, status: $status) {
      data {
        id
        case_id
        case_number
        title
        type
        priority
        status
        total_risk_score
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_STABLECOIN_RESERVES = gql`
  query StablecoinReserves($page: Int, $first: Int) {
    stablecoinReserves(page: $page, first: $first) {
      data {
        id
        reserve_id
        pool_id
        stablecoin_code
        asset_code
        amount
        value_usd
        allocation_percentage
        custodian_name
        status
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_PORTFOLIOS = gql`
  query Portfolios($page: Int, $first: Int) {
    portfolios(page: $page, first: $first) {
      data {
        id
        portfolio_id
        asset_class
        target_weight
        current_weight
        drift
        current_amount
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_BASKETS = gql`
  query Baskets($page: Int, $first: Int) {
    baskets(page: $page, first: $first) {
      data {
        id
        code
        name
        description
        type
        rebalance_frequency
        is_active
        last_rebalanced_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_CARDS = gql`
  query Cards($page: Int, $first: Int) {
    cards(page: $page, first: $first) {
      id
      card_token
      cardholder_name
      last_four
      network
      status
      label
      created_at
    }
  }
`

export const GET_BRIDGE_TRANSACTIONS = gql`
  query BridgeTransactions($page: Int, $first: Int) {
    bridgeTransactions(page: $page, first: $first) {
      data {
        id
        source_chain
        dest_chain
        token
        amount
        provider
        status
        source_tx_hash
        dest_tx_hash
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_DEFI_POSITIONS = gql`
  query DeFiPositions($page: Int, $first: Int) {
    defiPositions(page: $page, first: $first) {
      data {
        id
        protocol
        type
        status
        chain
        asset
        amount
        value_usd
        apy
        health_factor
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_FRAUD_CASES = gql`
  query FraudCases($page: Int, $first: Int) {
    fraudCases(page: $page, first: $first) {
      data {
        id
        uuid
        case_number
        status
        severity
        type
        amount
        risk_score
        description
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_AI_CONVERSATION = gql`
  query AiConversation($conversation_id: String!) {
    aiConversation(conversation_id: $conversation_id) {
      conversation_id
      response
      tokens_used
    }
  }
`

export const GET_REWARD_PROFILE = gql`
  query RewardProfile {
    rewardProfile {
      id
      xp
      level
      xp_for_next
      xp_progress
      current_streak
      longest_streak
      points_balance
      quests_completed
    }
  }
`

export const GET_AGENTS = gql`
  query Agents($page: Int, $first: Int) {
    agents(page: $page, first: $first) {
      data {
        id
        agent_id
        name
        status
        type
        capabilities
        relay_score
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_X402_PAYMENTS = gql`
  query X402Payments($page: Int, $first: Int, $status: String) {
    x402Payments(page: $page, first: $first, status: $status) {
      id
      payer_address
      pay_to_address
      amount
      network
      status
      transaction_hash
      created_at
    }
  }
`

export const GET_LEDGER_ACCOUNTS = gql`
  query LedgerAccounts {
    ledgerAccounts {
      code
      name
      type
      parent_code
      currency
      is_active
    }
  }
`

export const GET_TRIAL_BALANCE = gql`
  query LedgerTrialBalance {
    ledgerTrialBalance {
      account_code
      debit
      credit
      balance
    }
  }
`

export const GET_INVESTMENTS = gql`
  query Investments($page: Int, $first: Int) {
    investments(page: $page, first: $first) {
      data {
        id
        uuid
        amount
        status
        tier
        share_price
        shares_purchased
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_CUSTODIAN_ACCOUNTS = gql`
  query CustodianAccounts($page: Int, $first: Int) {
    custodianAccounts(page: $page, first: $first) {
      data {
        id
        account_uuid
        custodian_name
        status
        is_primary
        last_known_balance
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_SMART_ACCOUNTS = gql`
  query SmartAccounts($page: Int, $first: Int) {
    smartAccounts(page: $page, first: $first) {
      data {
        id
        owner_address
        account_address
        network
        deployed
        nonce
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_POLLS = gql`
  query Polls($page: Int, $first: Int) {
    polls(page: $page, first: $first) {
      data {
        id
        uuid
        title
        description
        type
        status
        start_date
        end_date
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_MERCHANTS = gql`
  query Merchants($page: Int, $first: Int) {
    merchants(page: $page, first: $first) {
      data {
        id
        public_id
        display_name
        status
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_PRODUCTS = gql`
  query Products($page: Int, $first: Int) {
    products(page: $page, first: $first) {
      data {
        id
        name
        description
        category
        type
        status
        features
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_REGULATORY_REPORTS = gql`
  query RegulatoryReports($page: Int, $first: Int) {
    regulatoryReports(page: $page, first: $first) {
      data {
        id
        report_type
        jurisdiction
        status
        due_date
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_PARTNERS = gql`
  query Partners($page: Int, $first: Int) {
    partners(page: $page, first: $first) {
      data {
        id
        institution_name
        legal_name
        institution_type
        country
        status
        tier
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_BATCH_JOBS = gql`
  query BatchJobs($page: Int, $first: Int) {
    batchJobs(page: $page, first: $first) {
      data {
        id
        name
        type
        status
        total_items
        processed_items
        failed_items
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_MFI_GROUPS = gql`
  query MfiGroups($page: Int, $first: Int) {
    mfiGroups(page: $page, first: $first) {
      data {
        id
        name
        status
        meeting_frequency
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_MOBILE_DEVICES = gql`
  query MobileDevices($page: Int, $first: Int) {
    mobileDevices(page: $page, first: $first) {
      data {
        id
        device_id
        platform
        device_name
        biometric_enabled
        is_trusted
        last_active_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_PAYMENT_INTENTS = gql`
  query PaymentIntents($page: Int, $first: Int) {
    paymentIntents(page: $page, first: $first) {
      data {
        id
        public_id
        asset
        network
        amount
        status
        created_at
      }
      paginatorInfo {
        total
        currentPage
        lastPage
        hasMorePages
      }
    }
  }
`

export const GET_ILP_ASSETS = gql`
  query IlpSupportedAssets {
    ilpSupportedAssets {
      code
      scale
    }
  }
`
