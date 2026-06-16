## 1) Scope and screens

### Tabs

* **Home / Shop / Activity / Wallet / Profile** (or as per UI/design)

### Key flows

1. **Pay merchant**

* Pay screen (amount + shield toggle + keypad) → Confirming → Success or Error → Transaction Details

2. **Receive**

* Select network + asset → show QR + address → share/copy

3. **Activity**

* Empty → List → Item → Transaction Details

4. **Errors**

* Merchant unreachable
* Wrong network
* Wrong token
* Insufficient fees
* Payment cancelled

---

## 2) Entities (data model)

### `Wallet`

* `walletId`
* `addresses[]`: `{ network, address }`
* `balances[]`: `{ asset, network, amount, usdApprox? }`
* `feeBalances[]`: `{ network, nativeAsset, amount }` (ex: SOL on Solana)

### `Merchant`

* `merchantId`
* `displayName`
* `iconUrl?`
* `accepts`: `{ asset, networks[] }`
* `terminalId` (or `sessionId`)

### `PaymentIntent` (BE creates this)

Represents “we are about to pay”.

* `intentId`
* `merchantId`
* `asset` (USDC)
* `network` (Solana)
* `amount`
* `status` (enum below)
* `shieldEnabled` (bool)
* `createdAt`, `expiresAt`
* `requiredConfirmations?` (for delayed screen)
* `feesEstimate?` (native + usd)

### `Transaction`

Created once broadcasted.

* `txId`
* `intentId`
* `network`
* `hash`
* `fromAddress`
* `toAddress`
* `amount`
* `fee`: `{ native, amount }`
* `confirmations`
* `status` (pending/confirmed/failed)
* `explorerUrl`

### `Receipt`

* `receiptId`
* `txId`
* `merchantName`
* `amount`
* `dateTime`
* `networkFee`
* `sharePayload` (deep link / image / text)

---

## 3) Status enums (single source of truth)

### `PaymentIntent.status`

* `CREATED` (pay screen ready)
* `AWAITING_AUTH` (pin/biometric/confirm)
* `SUBMITTING` (broadcasting tx)
* `PENDING` (on-chain pending, show Confirming)
* `CONFIRMED` (success)
* `FAILED` (generic fail)
* `CANCELLED` (user cancelled)
* `EXPIRED` (intent timed out)

### `PaymentError.code`

* `MERCHANT_UNREACHABLE`
* `WRONG_NETWORK`
* `WRONG_TOKEN`
* `INSUFFICIENT_FEES`
* `INSUFFICIENT_FUNDS` (optional separate from fees)
* `NETWORK_BUSY` (drives delayed confirming variant)

---

## 4) API contract (minimal)

### Create payment intent

`POST /v1/payments/intents`
Request:

```json
{ "merchantId":"...", "amount":"12.00", "asset":"USDC", "preferredNetwork":"SOLANA", "shield": true }
```

Response:

```json
{
  "intentId":"pi_...",
  "network":"SOLANA",
  "asset":"USDC",
  "amount":"12.00",
  "merchant":{"displayName":"Starbolt Coffee","iconUrl":"..."},
  "feesEstimate":{"nativeAsset":"SOL","amount":"0.00004","usdApprox":"0.01"},
  "status":"AWAITING_AUTH",
  "expiresAt":"..."
}
```

### Submit / authorize payment

`POST /v1/payments/intents/{intentId}/submit`
Request:

```json
{ "auth":"pin|biometric", "shield": true }
```

Response:

```json
{ "status":"SUBMITTING" }
```

### Poll intent status (or websocket)

`GET /v1/payments/intents/{intentId}`
Response:

```json
{
  "intentId":"pi_...",
  "status":"PENDING",
  "tx":{"hash":"...", "explorerUrl":"..."},
  "confirmations":12,
  "requiredConfirmations":30,
  "error": null
}
```

### Activity list

`GET /v1/activity?cursor=...`
Returns items with:

* `merchantName`, `amount`, `asset`, `timestamp`, `status`, `protected`

### Transaction details

`GET /v1/transactions/{txId}`
Returns:

* network, timestamp, referenceId, fees, explorerUrl, privacy flags

### Receive address

`GET /v1/wallet/receive?asset=USDC&network=SOLANA`
Returns:

* address + QR payload string

---

## 5) FE screen-to-data mapping

### Pay screen (keypad)

Needs:

* `PaymentIntent` (merchant, amount, asset, network, shield toggle)
* Fee estimate + check fee balance
  Actions:
* Toggle shield (local + send on submit)
* Confirm → `/submit`

### Confirming screen

Data:

* `intent.status`
* `tx.hash` once available
* `confirmations/requiredConfirmations`
  Rules:
* If `NETWORK_BUSY` or confirmations slow → show “may take a minute” variant
* If `CONFIRMED` → navigate to success

### Success screen

Data:

* final amount, merchant, date/time, “secured by ShieldPay”
  Actions:
* Share receipt (call receipt endpoint or generate local share)

### Errors

Mapped from `PaymentError.code`:

* Wrong network → CTA: Switch network
* Wrong token → CTA: Select USDC / swap (if supported)
* Insufficient fees → CTA: Add SOL / Receive SOL
* Merchant unreachable → Try again / Cancel

### Receive stablecoins

Data:

* selected `network`, `asset`
* `address`, `qrPayload`
  Actions:
* Copy address
* Share address
* View history (optional link to Activity filtered by incoming)

### Activity

* Empty state vs list
* Filters: all/income/expenses
* Item opens Transaction Details

### Transaction Details

Data:

* tx, network, timestamp, referenceId, fee, “protected” badge, receipt download/share

---

## 6) BE responsibilities (clear lines)

BE is source of truth for:

* merchant session / terminal reachability
* allowed asset/network per merchant
* fee estimation + fee insufficiency decision
* intent lifecycle + final state
* tx hash + confirmations (indexer integration)
* receipt generation payload (if you want consistent output)

FE is responsible for:

* UI state machine + routing
* local PIN/biometric UX
* polling/subscription strategy
* copy consistency and formatting

---

## 7) Edge cases you should define now (so devs don’t guess)

* **Timeouts:** intent expires at `expiresAt` → show “Payment expired” (missing screen if you want)
* **Retry semantics:** “Try again” creates new intent vs reuse existing
* **Partial broadcast:** if submit returns tx hash but later fails
* **Offline mode:** show “No connection” overlay
