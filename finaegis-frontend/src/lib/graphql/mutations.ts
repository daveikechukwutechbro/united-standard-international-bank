import { gql } from "@apollo/client"

export const UPDATE_PROFILE = gql`
  mutation UpdateProfile($input: UpdateProfileInput!) {
    updateProfile(input: $input) {
      id
      first_name
      last_name
      phone_number
      country
      city
      status
    }
  }
`

export const CREATE_ACCOUNT = gql`
  mutation CreateAccount($input: CreateAccountInput!) {
    createAccount(input: $input) {
      id
      uuid
      name
      balance
      created_at
    }
  }
`

export const FREEZE_ACCOUNT = gql`
  mutation FreezeAccount($input: FreezeAccountInput!) {
    freezeAccount(input: $input) {
      id
      frozen
    }
  }
`

export const UNFREEZE_ACCOUNT = gql`
  mutation UnfreezeAccount($id: ID!) {
    unfreezeAccount(id: $id) {
      id
      frozen
    }
  }
`

export const INITIATE_PAYMENT = gql`
  mutation InitiatePayment($input: InitiatePaymentInput!) {
    initiatePayment(input: $input) {
      id
      status
      amount
      currency
      reference
    }
  }
`

export const INITIATE_BANK_TRANSFER = gql`
  mutation InitiateBankTransfer($input: InitiateBankTransferInput!) {
    initiateBankTransfer(input: $input)
  }
`

export const PLACE_ORDER = gql`
  mutation PlaceOrder($input: PlaceOrderInput!) {
    placeOrder(input: $input) {
      id
      order_id
      type
      order_type
      base_currency
      quote_currency
      amount
      price
      status
    }
  }
`

export const CANCEL_ORDER = gql`
  mutation CancelOrder($order_id: String!) {
    cancelOrder(order_id: $order_id) {
      id
      status
    }
  }
`

export const CREATE_WALLET = gql`
  mutation CreateWallet($input: CreateWalletInput!) {
    createWallet(input: $input) {
      id
      name
      address
      chain
      status
    }
  }
`

export const TRANSFER_FUNDS = gql`
  mutation TransferFunds($input: TransferFundsInput!) {
    transferFunds(input: $input) {
      id
      status
    }
  }
`

export const APPLY_FOR_LOAN = gql`
  mutation ApplyForLoan($input: ApplyForLoanInput!) {
    applyForLoan(input: $input) {
      id
      status
      credit_score
      risk_rating
    }
  }
`

export const APPROVE_LOAN = gql`
  mutation ApproveLoan($input: ApproveLoanInput!) {
    approveLoan(input: $input) {
      id
      status
      approved_amount
      interest_rate
    }
  }
`

export const MINT_STABLECOIN = gql`
  mutation MintStablecoin($input: MintStablecoinInput!) {
    mintStablecoin(input: $input) {
      id
      amount
      value_usd
      status
    }
  }
`

export const REDEEM_STABLECOIN = gql`
  mutation RedeemStablecoin($input: RedeemStablecoinInput!) {
    redeemStablecoin(input: $input) {
      id
      amount
      status
    }
  }
`

export const SUBMIT_KYC = gql`
  mutation SubmitKycDocument($input: SubmitKycDocumentInput!) {
    submitKycDocument(input: $input) {
      id
      status
      document_type
      created_at
    }
  }
`

export const CREATE_PORTFOLIO = gql`
  mutation CreatePortfolio($input: CreatePortfolioInput!) {
    createPortfolio(input: $input) {
      id
      asset_class
      target_weight
      current_weight
    }
  }
`

export const REBALANCE_PORTFOLIO = gql`
  mutation RebalancePortfolio($input: RebalancePortfolioInput!) {
    rebalancePortfolio(input: $input) {
      id
      drift
    }
  }
`

export const CREATE_BASKET = gql`
  mutation CreateBasket($input: CreateBasketInput!) {
    createBasket(input: $input) {
      id
      name
      type
      is_active
    }
  }
`

export const REBALANCE_BASKET = gql`
  mutation RebalanceBasket($input: RebalanceBasketInput!) {
    rebalanceBasket(input: $input) {
      id
      last_rebalanced_at
    }
  }
`

export const SEND_AI_MESSAGE = gql`
  mutation SendAiMessage($input: SendAiMessageInput!) {
    sendAiMessage(input: $input) {
      conversation_id
      response
      tokens_used
    }
  }
`

export const INITIATE_BRIDGE_TRANSFER = gql`
  mutation InitiateBridgeTransfer($input: InitiateBridgeTransferInput!) {
    initiateBridgeTransfer(input: $input) {
      id
      source_chain
      dest_chain
      token
      amount
      status
    }
  }
`

export const CREATE_CARD = gql`
  mutation CreateCard($input: CreateCardInput!) {
    createCard(input: $input) {
      id
      card_token
      last_four
      network
      status
    }
  }
`

export const FREEZE_CARD = gql`
  mutation FreezeCard($id: ID!) {
    freezeCard(id: $id) {
      id
      status
    }
  }
`

export const CREATE_INVESTMENT = gql`
  mutation CreateInvestment($input: CreateInvestmentInput!) {
    createInvestment(input: $input) {
      id
      amount
      status
      shares_purchased
    }
  }
`

export const CREATE_POLL = gql`
  mutation CreatePoll($input: CreatePollInput!) {
    createPoll(input: $input) {
      id
      title
      status
    }
  }
`

export const CAST_VOTE = gql`
  mutation CastVote($input: CastVoteInput!) {
    castVote(input: $input) {
      id
      poll_id
      selected_options
    }
  }
`

export const CREATE_SMART_ACCOUNT = gql`
  mutation CreateSmartAccount($input: CreateSmartAccountInput!) {
    createSmartAccount(input: $input) {
      id
      account_address
      network
      deployed
    }
  }
`

export const OPEN_DEFI_POSITION = gql`
  mutation OpenPosition($input: OpenPositionInput!) {
    openPosition(input: $input) {
      id
      protocol
      type
      amount
      status
    }
  }
`

export const CLOSE_DEFI_POSITION = gql`
  mutation ClosePosition($input: ClosePositionInput!) {
    closePosition(input: $input) {
      id
      status
    }
  }
`

export const PROVISION_CARD = gql`
  mutation ProvisionCard($input: ProvisionCardInput!) {
    provisionCard(input: $input) {
      id
      card_token
      status
    }
  }
`

export const REGISTER_AGENT = gql`
  mutation RegisterAgent($input: RegisterAgentInput!) {
    registerAgent(input: $input) {
      id
      agent_id
      name
      status
    }
  }
`

export const CONNECT_BANK = gql`
  mutation ConnectBank($input: ConnectBankInput!) {
    connectBank(input: $input) {
      id
      bank_code
      status
    }
  }
`

export const LINK_CUSTODIAN_ACCOUNT = gql`
  mutation LinkCustodianAccount($input: LinkCustodianAccountInput!) {
    linkCustodianAccount(input: $input) {
      id
      custodian_name
      status
    }
  }
`
