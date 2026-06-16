# x402 Protocol - Comprehensive Technical Reference

> **Protocol Version**: 2 (v2.0, released 2025-12-09)
> **Repository**: https://github.com/coinbase/x402
> **Documentation**: https://docs.x402.org
> **Compiled**: 2026-02-22

---

## Table of Contents

1. [Protocol Overview](#1-protocol-overview)
2. [HTTP Transport Layer](#2-http-transport-layer)
3. [Core Type Definitions (TypeScript)](#3-core-type-definitions-typescript)
4. [EVM Payment Scheme Types](#4-evm-payment-scheme-types)
5. [Facilitator API](#5-facilitator-api)
6. [Payment Schemes](#6-payment-schemes)
7. [Network and Token Support](#7-network-and-token-support)
8. [Smart Contract Addresses and ABIs](#8-smart-contract-addresses-and-abis)
9. [Extensions](#9-extensions)
10. [SDK Implementation Guide](#10-sdk-implementation-guide)
11. [MCP Server Integration](#11-mcp-server-integration)
12. [Error Codes](#12-error-codes)
13. [Security Considerations](#13-security-considerations)

---

## 1. Protocol Overview

x402 is an open payment protocol that activates HTTP status code `402 Payment Required` for programmatic, crypto-native payments. It enables per-request API monetization, AI agent payments, content paywalls, and machine-to-machine commerce without accounts, sessions, or credentials.

### Architecture Components

```
Client (Buyer) <---> Resource Server (Seller) <---> Facilitator
       |                      |                         |
   Signs payment        Enforces payment         Verifies & Settles
   authorizations       requirements             on-chain transactions
```

**Three-layer architecture:**

| Layer | Purpose |
|-------|---------|
| **Types** | Transport and scheme-independent data structures |
| **Logic** | Scheme and network-dependent payment formation and verification |
| **Representation** | Transport-dependent data transmission (HTTP, MCP, A2A) |

### Payment Flow

```
1. Client  --> GET /resource         --> Server
2. Client  <-- 402 + PAYMENT-REQUIRED header  <-- Server
3. Client  --> GET /resource + PAYMENT-SIGNATURE header --> Server
4. Server  --> POST /verify          --> Facilitator
5. Server  <-- VerifyResponse        <-- Facilitator
6. Server  --> POST /settle          --> Facilitator
7. Server  <-- SettleResponse        <-- Facilitator
8. Client  <-- 200 + PAYMENT-RESPONSE header + body  <-- Server
```

---

## 2. HTTP Transport Layer

### HTTP Headers

| Header | Direction | Encoding | Content |
|--------|-----------|----------|---------|
| `PAYMENT-REQUIRED` | Server --> Client | Base64-encoded JSON | `PaymentRequired` object |
| `PAYMENT-SIGNATURE` | Client --> Server | Base64-encoded JSON | `PaymentPayload` object |
| `PAYMENT-RESPONSE` | Server --> Client | Base64-encoded JSON | `SettleResponse` object |

> **V1 --> V2 Header Migration**: `X-PAYMENT` became `PAYMENT-SIGNATURE`; `X-PAYMENT-RESPONSE` became `PAYMENT-RESPONSE`.

### HTTP Status Code Mapping

| Status | Condition |
|--------|-----------|
| `402 Payment Required` | Payment required or payment verification failed |
| `400 Bad Request` | Malformed payment payload |
| `500 Internal Server Error` | Internal payment processing error |
| `200 OK` | Successful payment + resource delivery |

### Encoding

All header payloads are JSON objects encoded as Base64 strings to ensure HTTP header compatibility and handle special characters safely.

---

## 3. Core Type Definitions (TypeScript)

Source: `@x402/core` package (`typescript/packages/core/src/types/`)

### Primitive Types

```typescript
// Network identifier in CAIP-2 format (e.g., "eip155:84532", "solana:5eykt4UsFv8P8NJdTREpY1vzqKqZKvdp")
export type Network = `${string}:${string}`;

// Monetary amount - either string ("$0.001"), number, or structured asset amount
export type Money = string | number;

// Structured asset amount with token address and atomic units
export type AssetAmount = {
  asset: string;        // Token contract address or currency code
  amount: string;       // Amount in atomic units (e.g., "1000" for $0.001 USDC with 6 decimals)
  extra?: Record<string, unknown>;  // Scheme-specific metadata
};

// Price can be human-readable or structured
export type Price = Money | AssetAmount;
```

### ResourceInfo

```typescript
export interface ResourceInfo {
  url: string;          // Protected resource URL
  description: string;  // Human-readable description
  mimeType: string;     // Expected response MIME type
}
```

### PaymentRequirements

```typescript
export type PaymentRequirements = {
  scheme: string;                    // Payment scheme identifier (e.g., "exact")
  network: Network;                  // CAIP-2 blockchain identifier (e.g., "eip155:84532")
  asset: string;                     // Token contract address (e.g., USDC address)
  amount: string;                    // Required amount in atomic units
  payTo: string;                     // Recipient wallet address
  maxTimeoutSeconds: number;         // Maximum completion time window
  extra: Record<string, unknown>;    // Scheme-specific data (e.g., EIP-712 domain for EVM)
};
```

### PaymentRequired (402 Response Body)

```typescript
export type PaymentRequired = {
  x402Version: number;                   // Protocol version (must be 2)
  error?: string;                        // Human-readable explanation
  resource: ResourceInfo;                // Protected resource description
  accepts: PaymentRequirements[];        // Array of acceptable payment methods
  extensions?: Record<string, unknown>;  // Protocol extensions
};
```

### PaymentPayload (Client Submission)

```typescript
export type PaymentPayload = {
  x402Version: number;                   // Protocol version identifier
  resource: ResourceInfo;                // Resource being purchased
  accepted: PaymentRequirements;         // Selected payment option from accepts[]
  payload: Record<string, unknown>;      // Scheme-specific payment data (signature + authorization)
  extensions?: Record<string, unknown>;  // Protocol extensions (echoed from server + client additions)
};
```

### PaymentPayloadResult (Mechanism Output)

```typescript
export type PaymentPayloadResult = Pick<PaymentPayload, "x402Version" | "payload"> & {
  extensions?: Record<string, unknown>;
};

export interface PaymentPayloadContext {
  extensions?: Record<string, unknown>;
}
```

### Verify Types

```typescript
export type VerifyRequest = {
  paymentPayload: PaymentPayload;
  paymentRequirements: PaymentRequirements;
};

export type VerifyResponse = {
  isValid: boolean;            // Whether the authorization is valid
  invalidReason?: string;      // Machine-readable error code
  invalidMessage?: string;     // Human-readable error description
  payer?: string;              // Recovered payer wallet address
  extensions?: Record<string, unknown>;
};
```

### Settle Types

```typescript
export type SettleRequest = {
  paymentPayload: PaymentPayload;
  paymentRequirements: PaymentRequirements;
};

export type SettleResponse = {
  success: boolean;            // Settlement status
  errorReason?: string;        // Machine-readable error code
  errorMessage?: string;       // Human-readable error description
  payer?: string;              // Payer wallet address
  transaction: string;         // Blockchain transaction hash
  network: Network;            // CAIP-2 network identifier
  extensions?: Record<string, unknown>;
};
```

### Supported Capabilities

```typescript
export type SupportedKind = {
  x402Version: number;                // Protocol version
  scheme: string;                     // Payment scheme (e.g., "exact")
  network: Network;                   // CAIP-2 network identifier
  extra?: Record<string, unknown>;    // Scheme configuration data
};

export type SupportedResponse = {
  kinds: SupportedKind[];             // Supported payment kinds
  extensions: string[];               // Supported extension identifiers
  signers: Record<string, string[]>;  // CAIP-2 patterns to signer addresses
};
```

### Error Classes

```typescript
export class VerifyError extends Error {
  readonly invalidReason?: string;
  readonly invalidMessage?: string;
  readonly payer?: string;
  readonly statusCode: number;

  constructor(statusCode: number, response: VerifyResponse);
}

export class SettleError extends Error {
  readonly errorReason?: string;
  readonly errorMessage?: string;
  readonly payer?: string;
  readonly transaction: string;
  readonly network: Network;
  readonly statusCode: number;

  constructor(statusCode: number, response: SettleResponse);
}
```

### Mechanism Interfaces

```typescript
// Client-side scheme implementation
export interface SchemeNetworkClient {
  readonly scheme: string;

  createPaymentPayload(
    x402Version: number,
    paymentRequirements: PaymentRequirements,
    context?: PaymentPayloadContext,
  ): Promise<PaymentPayloadResult>;
}

// Facilitator-side scheme implementation
export interface SchemeNetworkFacilitator {
  readonly scheme: string;
  readonly caipFamily: string;      // e.g., "eip155" or "solana"

  getExtra(network: Network): Record<string, unknown> | undefined;
  getSigners(network: string): string[];

  verify(
    payload: PaymentPayload,
    requirements: PaymentRequirements,
    context?: FacilitatorContext,
  ): Promise<VerifyResponse>;

  settle(
    payload: PaymentPayload,
    requirements: PaymentRequirements,
    context?: FacilitatorContext,
  ): Promise<SettleResponse>;
}

// Server-side scheme implementation
export interface SchemeNetworkServer {
  readonly scheme: string;

  parsePrice(price: Price, network: Network): Promise<AssetAmount>;

  enhancePaymentRequirements(
    paymentRequirements: PaymentRequirements,
    supportedKind: {
      x402Version: number;
      scheme: string;
      network: Network;
      extra?: Record<string, unknown>;
    },
    facilitatorExtensions: string[],
  ): Promise<PaymentRequirements>;
}

export type MoneyParser = (amount: number, network: Network) => Promise<AssetAmount | null>;
```

### Extension Interfaces

```typescript
export interface FacilitatorExtension {
  key: string;
}

export interface ResourceServerExtension {
  key: string;

  enrichDeclaration?: (declaration: unknown, transportContext: unknown) => unknown;

  enrichPaymentRequiredResponse?: (
    declaration: unknown,
    context: PaymentRequiredContext,
  ) => Promise<unknown>;

  enrichSettlementResponse?: (
    declaration: unknown,
    context: SettleResultContext,
  ) => Promise<unknown>;
}
```

### Zod Schema Validation

```typescript
// V2 Network validation: CAIP-2 format with colon separator
// Pattern: minimum 3 characters, must contain ":"
// Examples: "eip155:84532", "solana:5eykt4UsFv8P8NJdTREpY1vzqKqZKvdp"

// V1 Network validation: any non-empty string (backwards compatible)
// Examples: "base-sepolia", "base"

// Version discrimination: x402Version field differentiates V1 vs V2 payloads
```

---

## 4. EVM Payment Scheme Types

Source: `@x402/evm` package (`typescript/packages/mechanisms/evm/src/types.ts`)

### Asset Transfer Methods

```typescript
/**
 * - eip3009: Uses transferWithAuthorization (USDC, etc.) - recommended for compatible tokens
 * - permit2: Uses Permit2 + x402Permit2Proxy - universal fallback for any ERC-20
 */
export type AssetTransferMethod = "eip3009" | "permit2";
```

### EIP-3009 Payload

```typescript
export type ExactEIP3009Payload = {
  signature?: `0x${string}`;          // 65-byte EIP-712 signature
  authorization: {
    from: `0x${string}`;              // Payer wallet address
    to: `0x${string}`;                // Recipient wallet address
    value: string;                     // Amount in atomic units
    validAfter: string;                // Unix timestamp - activation time
    validBefore: string;               // Unix timestamp - expiration time
    nonce: `0x${string}`;             // 32-byte random nonce for replay prevention
  };
};
```

### Permit2 Types

```typescript
export type Permit2Witness = {
  to: `0x${string}`;                  // Enforced recipient address
  validAfter: string;                  // Activation timestamp
  extra: `0x${string}`;               // ABI-encoded additional data
};

export type Permit2Authorization = {
  permitted: {
    token: `0x${string}`;             // ERC-20 token contract address
    amount: string;                    // Permitted transfer amount
  };
  spender: `0x${string}`;             // x402ExactPermit2Proxy address (NOT facilitator)
  nonce: string;                       // Permit2 replay protection nonce
  deadline: string;                    // Expiration timestamp
  witness: Permit2Witness;             // Witness data with enforced recipient
};

export type ExactPermit2Payload = {
  signature: `0x${string}`;           // Authorization signature
  permit2Authorization: Permit2Authorization & {
    from: `0x${string}`;              // Payer address
  };
};

// Union type for V2 payloads
export type ExactEvmPayloadV2 = ExactEIP3009Payload | ExactPermit2Payload;

// Type guards
export function isPermit2Payload(payload: ExactEvmPayloadV2): payload is ExactPermit2Payload;
export function isEIP3009Payload(payload: ExactEvmPayloadV2): payload is ExactEIP3009Payload;
```

### EIP-712 Signing Types

```typescript
// EIP-3009 TransferWithAuthorization types for EIP-712 signing
export const authorizationTypes = {
  TransferWithAuthorization: [
    { name: "from", type: "address" },
    { name: "to", type: "address" },
    { name: "value", type: "uint256" },
    { name: "validAfter", type: "uint256" },
    { name: "validBefore", type: "uint256" },
    { name: "nonce", type: "bytes32" },
  ],
} as const;

// Permit2 EIP-712 types for signing PermitWitnessTransferFrom
// Types MUST be in ALPHABETICAL order after primary type (TokenPermissions < Witness)
export const permit2WitnessTypes = {
  PermitWitnessTransferFrom: [
    { name: "permitted", type: "TokenPermissions" },
    { name: "spender", type: "address" },
    { name: "nonce", type: "uint256" },
    { name: "deadline", type: "uint256" },
    { name: "witness", type: "Witness" },
  ],
  TokenPermissions: [
    { name: "token", type: "address" },
    { name: "amount", type: "uint256" },
  ],
  Witness: [
    { name: "to", type: "address" },
    { name: "validAfter", type: "uint256" },
    { name: "extra", type: "bytes" },
  ],
} as const;

// EIP-2612 Permit types
export const eip2612PermitTypes = {
  Permit: [
    { name: "owner", type: "address" },
    { name: "spender", type: "address" },
    { name: "value", type: "uint256" },
    { name: "nonce", type: "uint256" },
    { name: "deadline", type: "uint256" },
  ],
} as const;
```

---

## 5. Facilitator API

### Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| `POST` | `/verify` | Verify payment authorization without execution |
| `POST` | `/settle` | Execute verified payment on-chain |
| `GET` | `/supported` | List supported schemes, networks, extensions |
| `GET` | `/discovery/resources` | List discoverable x402 resources (Bazaar) |

### Facilitator URLs

| Environment | URL |
|-------------|-----|
| Testnet (default) | `https://x402.org/facilitator` |
| Production (Coinbase) | `https://api.cdp.coinbase.com/platform/v2/x402` |
| Self-hosted | Any URL implementing the facilitator interface |

### POST /verify

**Request:**
```json
{
  "paymentPayload": {
    "x402Version": 2,
    "resource": {
      "url": "https://api.example.com/weather",
      "description": "Weather data",
      "mimeType": "application/json"
    },
    "accepted": {
      "scheme": "exact",
      "network": "eip155:84532",
      "asset": "0x036CbD53842c5426634e7929541eC2318f3dCF7e",
      "amount": "1000",
      "payTo": "0xRecipientAddress",
      "maxTimeoutSeconds": 60,
      "extra": {}
    },
    "payload": {
      "signature": "0x...",
      "authorization": {
        "from": "0xPayerAddress",
        "to": "0xRecipientAddress",
        "value": "1000",
        "validAfter": "0",
        "validBefore": "1740268800",
        "nonce": "0x..."
      }
    }
  },
  "paymentRequirements": {
    "scheme": "exact",
    "network": "eip155:84532",
    "asset": "0x036CbD53842c5426634e7929541eC2318f3dCF7e",
    "amount": "1000",
    "payTo": "0xRecipientAddress",
    "maxTimeoutSeconds": 60,
    "extra": {}
  }
}
```

**Success Response (200):**
```json
{
  "isValid": true,
  "payer": "0xPayerAddress"
}
```

**Failure Response (200 with isValid=false):**
```json
{
  "isValid": false,
  "invalidReason": "insufficient_funds",
  "invalidMessage": "Payer balance 500 is less than required 1000",
  "payer": "0xPayerAddress"
}
```

### POST /settle

**Request:** Identical structure to `/verify`

**Success Response (200):**
```json
{
  "success": true,
  "payer": "0xPayerAddress",
  "transaction": "0xTransactionHash...",
  "network": "eip155:84532"
}
```

**Failure Response (200 with success=false):**
```json
{
  "success": false,
  "errorReason": "invalid_transaction_state",
  "errorMessage": "Transaction reverted",
  "payer": "0xPayerAddress",
  "transaction": "",
  "network": "eip155:84532"
}
```

### GET /supported

**Response:**
```json
{
  "kinds": [
    {
      "x402Version": 2,
      "scheme": "exact",
      "network": "eip155:84532",
      "extra": {
        "name": "USD Coin",
        "version": "2"
      }
    },
    {
      "x402Version": 2,
      "scheme": "exact",
      "network": "solana:EtWTRABZaYq6iMfeYKouRu166VU2xqa1"
    }
  ],
  "extensions": ["payment-identifier", "sign-in-with-x", "bazaar"],
  "signers": {
    "eip155:84532": ["0xFacilitatorSignerAddress"],
    "solana:EtWTRABZaYq6iMfeYKouRu166VU2xqa1": ["SolanaFacilitatorPubkey"]
  }
}
```

### GET /discovery/resources (Bazaar)

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `type` | string | No | -- | Filter by resource type (e.g., "http") |
| `limit` | number | No | 20 | Max results (1-100) |
| `offset` | number | No | 0 | Pagination offset |

**Response:**
```json
{
  "x402Version": 2,
  "items": [
    {
      "resource": "https://api.example.com/x402/weather",
      "type": "http",
      "x402Version": 2,
      "accepts": [
        {
          "scheme": "exact",
          "network": "eip155:8453",
          "asset": "0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913",
          "maxAmountRequired": "200",
          "maxTimeoutSeconds": 60,
          "payTo": "0xa2477E16dCB42E2AD80f03FE97D7F1a1646cd1c0",
          "mimeType": "",
          "description": "",
          "outputSchema": {
            "input": { "method": "GET", "type": "http" },
            "output": null
          }
        }
      ],
      "lastUpdated": "2025-08-09T01:07:04.005Z",
      "metadata": {}
    }
  ],
  "pagination": {
    "limit": 10,
    "offset": 0,
    "total": 1
  }
}
```

---

## 6. Payment Schemes

### 6.1 Exact Scheme - EVM (EIP-3009)

The recommended method for tokens with native `transferWithAuthorization` support (e.g., USDC).

**EIP-712 Domain** (provided in `extra` field of PaymentRequirements):
```json
{
  "name": "USD Coin",
  "version": "2"
}
```

**Verification Steps (sequential):**

1. Recover signer from EIP-712 signature, verify it matches `authorization.from`
2. Check payer has sufficient token balance via `balanceOf(from)`
3. Verify `authorization.value` >= `paymentRequirements.amount`
4. Verify `authorization.validAfter` <= current time <= `authorization.validBefore`
5. Verify `authorization.to` matches `paymentRequirements.payTo`
6. Verify token and network match specifications
7. Simulate `transferWithAuthorization()` call on-chain

**Settlement:**
Facilitator calls `transferWithAuthorization(from, to, value, validAfter, validBefore, nonce, signature)` on the ERC-20 token contract.

### 6.2 Exact Scheme - EVM (Permit2)

Universal fallback for any ERC-20 token via Uniswap Permit2 protocol.

**Approval Pathways:**

| Option | Description | Gas Cost |
|--------|-------------|----------|
| Direct Approval | User pays gas for `approve(Permit2)` | User pays |
| Sponsored ERC20 | Facilitator covers approval gas via extension | Facilitator pays |
| EIP-2612 Permit | Token signs permit; facilitator calls `settleWithPermit()` | Facilitator pays |

**Verification Steps:**

1. Recover signer from signature, verify matches `permit2Authorization.from`
2. Verify Permit2 allowance exists (if insufficient: check for sponsored/EIP-2612 fallback)
3. Confirm sufficient token balance
4. Verify authorization amount covers payment
5. Validate deadline (not expired) and `witness.validAfter` (active)
6. Confirm token and network match requirements
7. Simulate settlement via appropriate method

**Settlement Methods:**
- Standard: `x402ExactPermit2Proxy.settle(permit, owner, witness, signature)`
- Sponsored: Batch `transfer` -> `approve` -> `settle`
- EIP-2612: `x402ExactPermit2Proxy.settleWithPermit(permit2612, permit, owner, witness, signature)`

### 6.3 Exact Scheme - Solana (SVM)

Uses SPL token `TransferChecked` instruction.

**Requirements:**
- Strict instruction layout: Compute Unit Limit, Compute Unit Price, TransferChecked
- Facilitator fee payer excluded from instruction accounts, transfer authority, and source
- Compute unit price bounded against gas abuse
- Destination ATA verification against `payTo`/`asset` PDA
- Transfer amount must exactly equal `PaymentRequirements.amount`

---

## 7. Network and Token Support

### Supported Networks (CAIP-2 Format)

| Network | CAIP-2 Identifier | Chain ID | Type | Environment |
|---------|-------------------|----------|------|-------------|
| Base Sepolia | `eip155:84532` | 84532 | EVM | Testnet |
| Base | `eip155:8453` | 8453 | EVM | Mainnet |
| Ethereum | `eip155:1` | 1 | EVM | Mainnet |
| Sepolia | `eip155:11155111` | 11155111 | EVM | Testnet |
| Avalanche Fuji | `eip155:43113` | 43113 | EVM | Testnet |
| Avalanche | `eip155:43114` | 43114 | EVM | Mainnet |
| Solana Devnet | `solana:EtWTRABZaYq6iMfeYKouRu166VU2xqa1` | -- | SVM | Testnet |
| Solana | `solana:5eykt4UsFv8P8NJdTREpY1vzqKqZKvdp` | -- | SVM | Mainnet |

### CAIP-2 Format Standards

- **EVM**: `eip155:<chainId>` (numeric chain identifier)
- **Solana**: `solana:<genesisHash>` (first 32 bytes of genesis block hash)

### V1 to V2 Network ID Migration

| V1 Name | V2 CAIP-2 ID |
|---------|-------------|
| `base-sepolia` | `eip155:84532` |
| `base` | `eip155:8453` |
| `ethereum` | `eip155:1` |
| `sepolia` | `eip155:11155111` |
| `solana-devnet` | `solana:EtWTRABZaYq6iMfeYKouRu166VU2xqa1` |
| `solana` | `solana:5eykt4UsFv8P8NJdTREpY1vzqKqZKvdp` |

### Token Support

**EVM Networks**: Must implement EIP-3009 (`transferWithAuthorization`) for the primary flow, or any ERC-20 via Permit2 fallback.

**Solana**: Supports all SPL tokens and Token-2022 program tokens.

**USDC Contract Addresses** (primary supported token):

| Network | USDC Address | Decimals |
|---------|-------------|----------|
| Base Mainnet | `0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913` | 6 |
| Base Sepolia | `0x036CbD53842c5426634e7929541eC2318f3dCF7e` | 6 |

**EIP-712 Domain for USDC**: Retrieve `name()` and `version()` from the token contract:
```json
{
  "name": "USD Coin",
  "version": "2"
}
```

### Facilitator Network Support

| Facilitator | Supported Networks |
|-------------|-------------------|
| x402.org (testnet default) | `eip155:84532`, `solana:EtWTRABZaYq6iMfeYKouRu166VU2xqa1` |
| Coinbase CDP (production) | Base, Solana, Polygon, Avalanche, and more |
| Self-hosted | Any EVM network using CAIP-2 format |

---

## 8. Smart Contract Addresses and ABIs

### Deployed Contract Addresses

All addresses are deterministic via CREATE2 deployment (Arachnid's factory):

| Contract | Address | Purpose |
|----------|---------|---------|
| Permit2 (Uniswap) | `0x000000000022D473030F116dDEE9F6B43aC78BA3` | Universal ERC-20 permit system |
| x402ExactPermit2Proxy | `0x4020615294c913F045dc10f0a5cdEbd86c280001` | Exact-amount Permit2 settlement |
| x402UptoPermit2Proxy | `0x4020633461b2895a48930Ff97eE8fCdE8E520002` | Variable-amount Permit2 settlement |

> Vanity addresses with `0x4020` prefix for easy recognition. Same address on all EVM chains.

### EIP-3009 ABI

```json
[
  {
    "inputs": [
      { "name": "from", "type": "address" },
      { "name": "to", "type": "address" },
      { "name": "value", "type": "uint256" },
      { "name": "validAfter", "type": "uint256" },
      { "name": "validBefore", "type": "uint256" },
      { "name": "nonce", "type": "bytes32" },
      { "name": "v", "type": "uint8" },
      { "name": "r", "type": "bytes32" },
      { "name": "s", "type": "bytes32" }
    ],
    "name": "transferWithAuthorization",
    "outputs": [],
    "stateMutability": "nonpayable",
    "type": "function"
  },
  {
    "inputs": [
      { "name": "from", "type": "address" },
      { "name": "to", "type": "address" },
      { "name": "value", "type": "uint256" },
      { "name": "validAfter", "type": "uint256" },
      { "name": "validBefore", "type": "uint256" },
      { "name": "nonce", "type": "bytes32" },
      { "name": "signature", "type": "bytes" }
    ],
    "name": "transferWithAuthorization",
    "outputs": [],
    "stateMutability": "nonpayable",
    "type": "function"
  },
  {
    "inputs": [{ "name": "account", "type": "address" }],
    "name": "balanceOf",
    "outputs": [{ "name": "", "type": "uint256" }],
    "stateMutability": "view",
    "type": "function"
  },
  {
    "inputs": [],
    "name": "version",
    "outputs": [{ "name": "", "type": "string" }],
    "stateMutability": "view",
    "type": "function"
  }
]
```

### x402ExactPermit2Proxy ABI (Key Functions)

```solidity
// Solidity struct definitions
struct Witness {
    address to;
    uint256 validAfter;
    bytes extra;
}

struct EIP2612Permit {
    uint256 value;
    uint256 deadline;
    bytes32 r;
    bytes32 s;
    uint8 v;
}
```

**Functions:**

| Function | Parameters | Purpose |
|----------|------------|---------|
| `settle` | `permit, owner, witness, signature` | Standard Permit2 settlement |
| `settleWithPermit` | `permit2612, permit, owner, witness, signature` | EIP-2612 + Permit2 settlement |
| `PERMIT2()` | -- | Returns canonical Permit2 address |
| `WITNESS_TYPEHASH()` | -- | Returns witness type hash |
| `initialize(_permit2)` | address | One-time initialization |

**Events:** `Settled`, `SettledWithPermit`

**Errors:** `AlreadyInitialized`, `InvalidDestination`, `InvalidOwner`, `InvalidPermit2Address`, `PaymentTooEarly`, `ReentrancyGuardReentrantCall`, `AmountExceedsPermitted` (upto proxy only)

**EIP-712 Witness Type String:**
```
Witness witness)Witness(bytes extra,address to,uint256 validAfter)TokenPermissions(address token,uint256 amount)
```

---

## 9. Extensions

### 9.1 Payment Identifier Extension

Provides idempotent retry capability for payment requests.

**Constants:**
```typescript
const PAYMENT_IDENTIFIER = "payment-identifier";
const PAYMENT_ID_MIN_LENGTH = 16;
const PAYMENT_ID_MAX_LENGTH = 128;
const PAYMENT_ID_PATTERN = /^[a-zA-Z0-9_-]+$/;
```

**Format:** `pay_<32-character-hex-string>` (e.g., `pay_7d5d747be160e280504c099d984bcfe0`)

**Client API:**
```typescript
generatePaymentId(): string;
appendPaymentIdentifierToExtensions(extensions, paymentId?): void;
isValidPaymentId(id: string): boolean;
```

**Server API:**
```typescript
declarePaymentIdentifierExtension(required: boolean): void;
extractPaymentIdentifier(paymentPayload: PaymentPayload): string | null;
validatePaymentIdentifier(paymentPayload: PaymentPayload): { valid: boolean; error?: string };
```

**Cache Behavior:**

| Scenario | Action |
|----------|--------|
| New ID | Process payment, cache response |
| Same ID within TTL | Return cached response, skip payment |
| Same ID after TTL | Process payment, update cache |
| No ID provided | Process payment (no caching) |

**Recommended TTL:** 5-15 minutes (time-sensitive) or 1-24 hours (static resources)

### 9.2 Sign-In-With-X (SIWX) Extension

Implements CAIP-122 for chain-agnostic wallet authentication. Allows users who have previously paid to access resources using wallet signature authentication.

**HTTP Header:** `SIGN-IN-WITH-X` (base64-encoded)

**402 Response Extension Structure:**
```json
{
  "sign-in-with-x": {
    "domain": "api.example.com",
    "resourceUri": "https://api.example.com/weather",
    "network": "eip155:8453",
    "statement": "Sign in to access weather data",
    "supportedChains": [
      { "chainId": "eip155:8453", "type": "evm" }
    ],
    "info": {
      "nonce": "unique-random-nonce",
      "issuedAt": "2026-02-22T12:00:00Z",
      "expirationTime": "2026-02-22T12:05:00Z",
      "notBefore": "2026-02-22T12:00:00Z"
    }
  }
}
```

**Server-Side Declaration:**
```typescript
declareSIWxExtension({
  domain?: string,              // Auto-derived from request URL
  resourceUri?: string,         // Auto-derived from request URL
  network?: string | string[],  // From accepts[].network
  statement?: string,           // Human-readable purpose
  version?: string,             // CAIP-122 version (default "1")
  expirationSeconds?: number,   // Challenge TTL
});
```

**Validation:**
```typescript
validateSIWxMessage(payload, resourceUri, {
  maxAge?: number,                     // Default: 5 minutes
  checkNonce?: (nonce) => boolean,     // Custom nonce validation
}): { valid: boolean; error?: string };

verifySIWxSignature(payload, {
  evmVerifier?: EVMMessageVerifier,    // For smart wallets (EIP-1271/6492)
}): { valid: boolean; address?: string; error?: string };
```

**Wallet Signing:**

| Chain | Message Format | Signature Type | Schemes |
|-------|---------------|----------------|---------|
| EVM | EIP-4361 (SIWE) | `eip191` | `eip191` (EOA), `eip1271` (smart contract), `eip6492` (counterfactual) |
| Solana | Sign-In With Solana | `ed25519` | `ed25519` |

**Storage Interface:**
```typescript
interface SIWxStorage {
  hasPaid(address: string, resourceUri: string): Promise<boolean>;
  recordPayment(address: string, resourceUri: string): Promise<void>;
}
```

### 9.3 Bazaar Discovery Extension

Machine-readable API catalog for service discovery.

**Route Configuration:**
```typescript
{
  "GET /weather": {
    price: "$0.001",
    network: "eip155:8453",
    resource: "0xRecipientAddress",
    description: "Get current weather data",
    extensions: {
      bazaar: {
        discoverable: true,
        inputSchema: { queryParams: { /* JSON Schema */ } },
        outputSchema: { properties: { /* JSON Schema */ } }
      }
    }
  }
}
```

**Discovery Endpoint:** `GET https://api.cdp.coinbase.com/platform/v2/x402/discovery/resources`

---

## 10. SDK Implementation Guide

### Supported Languages and Frameworks

| Feature | TypeScript | Go | Python |
|---------|------------|-----|--------|
| **Server Frameworks** | Express, Hono, Next.js | Gin | FastAPI, Flask |
| **HTTP Clients** | Fetch, Axios | net/http | httpx, requests |
| **EVM Support** | Yes | Yes | Yes |
| **SVM Support** | Yes | Yes | Yes |
| **EIP-3009 Exact** | Yes | Yes | Yes |
| **SPL Exact** | Yes | Yes | Yes |
| **SIWX Extension** | Yes | No | No |
| **Payment Identifier** | Yes | No | No |
| **HTTP Lifecycle Hooks** | Yes | No | No |
| **Dynamic Pricing** | Yes | Yes | Yes |
| **Browser Paywall UI** | Yes | Yes | Yes |

### Installation

**TypeScript:**
```bash
# Server
npm install @x402/express @x402/core @x402/evm   # or @x402/next, @x402/hono
# Client
npm install @x402/fetch @x402/evm                  # or @x402/axios
# Solana support
npm install @x402/svm
```

**Go:**
```bash
go get github.com/coinbase/x402/go
```

**Python:**
```bash
pip install "x402[fastapi]"   # or "x402[flask]"
pip install "x402[httpx]"     # async client, or "x402[requests]" for sync
pip install "x402[svm]"       # Solana support
```

### Wallet/Signer Configuration

**TypeScript (EVM with viem):**
```typescript
import { privateKeyToAccount } from "viem/accounts";
const signer = privateKeyToAccount(process.env.EVM_PRIVATE_KEY as `0x${string}`);
```

**Go (EVM):**
```go
evmSigner, err := evmsigners.NewClientSignerFromPrivateKey(os.Getenv("EVM_PRIVATE_KEY"))
```

**Python (EVM with eth-account):**
```python
from eth_account import Account
from x402.mechanisms.evm import EthAccountSigner
account = Account.from_key(os.getenv("EVM_PRIVATE_KEY"))
signer = EthAccountSigner(account)
```

**Solana (TypeScript):**
```typescript
import { createKeyPairSignerFromBytes } from "@solana/kit";
import { base58 } from "@scure/base";
const svmSigner = await createKeyPairSignerFromBytes(
  base58.decode(process.env.SVM_PRIVATE_KEY)
);
```

### Client Setup (TypeScript)

```typescript
import { x402Client, wrapAxiosWithPayment } from "@x402/axios";
import { registerExactEvmScheme } from "@x402/evm/exact/client";
import { registerExactSvmScheme } from "@x402/svm/exact/client";

// Create client and register schemes
const client = new x402Client();

// EVM scheme (matches eip155:* networks)
const evmSigner = privateKeyToAccount(process.env.EVM_PRIVATE_KEY as `0x${string}`);
registerExactEvmScheme(client, { signer: evmSigner });

// Solana scheme (matches solana:* networks)
const svmSigner = await createKeyPairSignerFromBytes(base58.decode(process.env.SVM_PRIVATE_KEY));
registerExactSvmScheme(client, { signer: svmSigner });

// Wrap HTTP client - automatic payment handling
const api = wrapAxiosWithPayment(axios.create({ baseURL }), client);

// Or with fetch
import { wrapFetchWithPayment } from "@x402/fetch";
const wrappedFetch = wrapFetchWithPayment(fetch, client);
```

### Server Setup (TypeScript / Express)

```typescript
import express from "express";
import { x402ResourceServer } from "@x402/core/server";
import { HTTPFacilitatorClient } from "@x402/core/facilitator";
import { x402ExpressMiddleware } from "@x402/express";
import { registerExactEvmScheme } from "@x402/evm/exact/server";

const app = express();

// Create resource server with facilitator
const facilitatorClient = new HTTPFacilitatorClient("https://x402.org/facilitator");
const resourceServer = new x402ResourceServer(facilitatorClient);
registerExactEvmScheme(resourceServer);

// Route configuration
const routes = {
  "GET /weather": {
    scheme: "exact",
    price: "$0.001",
    network: "eip155:84532",
    payTo: "0xYourWalletAddress",
    description: "Get weather data",
    mimeType: "application/json",
  }
};

// Apply middleware
app.use(x402ExpressMiddleware(resourceServer, routes));

app.get("/weather", (req, res) => {
  res.json({ weather: "sunny", temperature: 70 });
});
```

### Facilitator Registration (TypeScript)

```typescript
import { x402Facilitator } from "@x402/core/facilitator";
import { registerExactEvmScheme } from "@x402/evm/exact/facilitator/register";

interface EvmFacilitatorConfig {
  signer: FacilitatorEvmSigner;
  networks: Network | Network[];
  deployERC4337WithEIP6492?: boolean;  // Default: false
}

const facilitator = new x402Facilitator();
registerExactEvmScheme(facilitator, {
  signer: combinedClient,
  networks: ["eip155:84532", "eip155:8453"],
});
```

### Retrieving Settlement Confirmation

```typescript
const paymentResponse = httpClient.getPaymentSettleResponse(
  (name) => response.headers.get(name)
);
// Returns SettleResponse with transaction hash
```

---

## 11. MCP Server Integration

Integration with Claude Desktop via Model Context Protocol for automated AI agent payments.

### Prerequisites

- Node.js v20+, pnpm v10
- x402-compatible server
- Ethereum wallet with USDC or Solana wallet with USDC

### Claude Desktop Configuration

```json
{
  "mcpServers": {
    "demo": {
      "command": "pnpm",
      "args": ["--silent", "-C", "<path>/examples/typescript/clients/mcp", "dev"],
      "env": {
        "EVM_PRIVATE_KEY": "<0x-prefixed private key>",
        "SVM_PRIVATE_KEY": "<base58-encoded Solana key>",
        "RESOURCE_SERVER_URL": "http://localhost:4021",
        "ENDPOINT_PATH": "/weather"
      }
    }
  }
}
```

### Environment Variables

| Variable | Purpose | Required |
|----------|---------|----------|
| `EVM_PRIVATE_KEY` | EVM wallet private key (0x-prefixed) | One of EVM/SVM required |
| `SVM_PRIVATE_KEY` | Solana private key (base58-encoded) | One of EVM/SVM required |
| `RESOURCE_SERVER_URL` | Paid API base URL | Yes |
| `ENDPOINT_PATH` | Specific endpoint path | Yes |

### MCP Server Tool Implementation

```typescript
import { McpServer } from "@modelcontextprotocol/sdk";

const server = new McpServer({
  name: "x402 MCP Client Demo",
  version: "2.0.0",
});

server.tool(
  "get-data-from-resource-server",
  "Fetch data from the resource server with automatic payment handling",
  {},
  async () => {
    const res = await api.get(endpointPath);
    return {
      content: [{ type: "text", text: JSON.stringify(res.data) }],
    };
  },
);
```

### Dependencies

```json
{
  "@modelcontextprotocol/sdk": "^1.9.0",
  "@x402/axios": "workspace:*",
  "@x402/evm": "workspace:*",
  "@x402/svm": "workspace:*",
  "axios": "^1.13.2",
  "viem": "^2.39.0",
  "@solana/kit": "^2.1.1",
  "@scure/base": "^1.2.6"
}
```

---

## 12. Error Codes

### Payment Verification Errors

| Error Code | Description |
|------------|-------------|
| `insufficient_funds` | Payer has insufficient token balance |
| `invalid_exact_evm_payload_authorization_valid_after` | Authorization not yet valid |
| `invalid_exact_evm_payload_authorization_valid_before` | Authorization has expired |
| `invalid_exact_evm_payload_authorization_value` | Insufficient payment amount |
| `invalid_exact_evm_payload_signature` | Invalid or improperly signed authorization |
| `invalid_exact_evm_payload_recipient_mismatch` | Recipient address mismatch |

### Configuration Errors

| Error Code | Description |
|------------|-------------|
| `invalid_network` | Unsupported blockchain network |
| `invalid_scheme` | Unsupported payment scheme |
| `unsupported_scheme` | Facilitator does not support scheme |
| `invalid_x402_version` | Unsupported protocol version |

### Data Structure Errors

| Error Code | Description |
|------------|-------------|
| `invalid_payload` | Malformed payment payload |
| `invalid_payment_requirements` | Invalid requirements object |

### Execution Errors

| Error Code | Description |
|------------|-------------|
| `invalid_transaction_state` | Transaction failed or rejected on-chain |
| `unexpected_verify_error` | Unexpected verification failure |
| `unexpected_settle_error` | Unexpected settlement failure |

### Permit2-Specific Errors

| Error Code | Description |
|------------|-------------|
| `PERMIT2_ALLOWANCE_REQUIRED` | Permit2 allowance insufficient (HTTP 412) |

### Smart Contract Errors

| Error | Description |
|-------|-------------|
| `AlreadyInitialized` | Proxy contract already initialized |
| `AmountExceedsPermitted` | Transfer amount exceeds permitted (upto proxy) |
| `InvalidDestination` | Invalid recipient address |
| `InvalidOwner` | Invalid owner address |
| `InvalidPermit2Address` | Invalid Permit2 contract address |
| `PaymentTooEarly` | Payment before `validAfter` timestamp |
| `ReentrancyGuardReentrantCall` | Reentrancy attack detected |

---

## 13. Security Considerations

### Replay Attack Prevention

- **EIP-3009 Nonce**: Unique 32-byte random nonce per authorization
- **Blockchain Protection**: EIP-3009 contracts prevent nonce reuse on-chain
- **Time Constraints**: Explicit `validAfter` / `validBefore` time windows
- **Signature Verification**: Cryptographic validation tied to payer address

### Facilitator Security

- Facilitators **cannot** modify amounts or destinations (enforced via signature verification)
- Permit2 proxy uses witness pattern to prevent receiver manipulation
- Facilitator fee payer excluded from sensitive transaction accounts (Solana)

### Extension Security

- **Domain Binding**: SIWX signatures tied to specific service domain
- **Nonce Uniqueness**: Each SIWX challenge requires distinct nonce
- **Temporal Constraints**: `issuedAt`, `expirationTime`, `notBefore` restrict validity windows
- **Smart Wallet Support**: EIP-1271/6492 verification requires RPC call

### Transport Combinations

| Transport | Specification |
|-----------|--------------|
| HTTP | `transports-v2/http.md` |
| MCP | `transports-v2/mcp.md` |
| A2A (Agent-to-Agent) | `transports-v2/a2a.md` |

---

## Lifecycle Hooks

### Server Hooks (x402ResourceServer)

**Verification Lifecycle:**
```typescript
onBeforeVerify:   (payload, requirements) => { abort?: true, reason?: string } | void
onAfterVerify:    (payload, requirements, result) => void
onVerifyFailure:  (error, payload, requirements) => { recovered?: true, result } | void
```

**Settlement Lifecycle:**
```typescript
onBeforeSettle:   (payload, requirements) => { abort?: true, reason?: string } | void
onAfterSettle:    (payload, requirements, result) => void
onSettleFailure:  (error, payload, requirements) => { recovered?: true, result } | void
```

**HTTP-Specific:**
```typescript
onProtectedRequest: (request) => { grantAccess: true } | { abort: true, reason } | void
```

### Client Hooks (x402Client)

```typescript
onBeforePaymentCreation:   (requirements) => { abort?: true, reason?: string } | void
onAfterPaymentCreation:    (requirements, payload) => void
onPaymentCreationFailure:  (error, requirements) => { recovered?: true, payload } | void
```

**HTTP-Specific:**
```typescript
onPaymentRequired: (response) => { headers } | void
```

### Facilitator Hooks

Mirrors server patterns with verification/settlement lifecycle support for cross-system processing.

---

## Appendix: Complete V1 to V2 Migration Reference

| Aspect | V1 | V2 |
|--------|-----|-----|
| Protocol version field | `x402Version: 1` | `x402Version: 2` |
| Request payment header | `X-PAYMENT` | `PAYMENT-SIGNATURE` |
| Response payment header | `X-PAYMENT-RESPONSE` | `PAYMENT-RESPONSE` |
| Network identifiers | String names (`base-sepolia`) | CAIP-2 (`eip155:84532`) |
| Amount field name | `maxAmountRequired` | `amount` |
| Resource info | Embedded in requirements | Separate `ResourceInfo` object |
| Route config | Inline `{ price, network }` | `accepts[]` array with scheme |
| Client architecture | Wallet/signer passed to middleware | `x402Client` with registered schemes |
| Server architecture | Decorator-based | `x402ResourceServer` class |
| Extensions | Not supported | Structured extension system |
| Permit2 support | Not supported | Full Permit2 + proxy support |
| Multi-chain client | Not supported | Single client, multiple schemes |
