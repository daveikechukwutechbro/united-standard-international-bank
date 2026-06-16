# API Documentation

This directory contains REST API documentation for the FinAegis platform.

## Contents

- **[REST_API_REFERENCE.md](REST_API_REFERENCE.md)** - Consolidated REST API reference with all endpoints
- **[API_VOTING_ENDPOINTS.md](API_VOTING_ENDPOINTS.md)** - Specific documentation for voting and governance APIs
- **[WEBHOOK_INTEGRATION.md](WEBHOOK_INTEGRATION.md)** - Webhook configuration and integration guide
- **[BIAN_API_DOCUMENTATION.md](BIAN_API_DOCUMENTATION.md)** - BIAN standard API documentation
- **[OPENAPI_COVERAGE_100_PERCENT.md](OPENAPI_COVERAGE_100_PERCENT.md)** - OpenAPI specification coverage analysis
- **[SERVICES_REFERENCE.md](SERVICES_REFERENCE.md)** - Service layer reference documentation

## Purpose

These documents provide:
- Complete API endpoint reference
- Request/response examples
- Authentication requirements
- Error handling patterns
- Rate limiting information
- Webhook integration guidance
- API best practices

## Current API Status (February 2026)

### v2.4.0 API Endpoints (February 1, 2026)
- 🚧 **Privacy APIs**: Zero-Knowledge KYC verification
  - `POST /api/privacy/zkkyc/verify` - Verify without exposing PII
  - `POST /api/privacy/selective-disclosure` - Prove specific claims
  - `POST /api/privacy/proof-of-innocence` - Generate compliance proofs

- 🚧 **Commerce APIs**: Soulbound tokens, attestations
  - `POST /api/commerce/soulbound-tokens` - Issue SBT
  - `POST /api/commerce/merchants/onboard` - Merchant onboarding
  - `POST /api/commerce/attestations` - Payment attestations

- 🚧 **TrustCert APIs**: Verifiable credentials
  - `POST /api/trustcert/credentials` - Issue credential
  - `POST /api/trustcert/verify` - Verify credential
  - `GET /api/trustcert/revocations` - Check revocation status

### v2.2.0 API Endpoints (January 31, 2026)
- ✅ **Mobile Device APIs**: Device registration and management
- ✅ **Biometric APIs**: Biometric authentication
- ✅ **Push Notification APIs**: FCM/APNs integration

### v2.1.0 API Endpoints (January 30, 2026)
- ✅ **Hardware Wallet APIs**: Device registration, signing requests
  - `POST /api/hardware-wallet/register` - Register Ledger/Trezor device
  - `POST /api/hardware-wallet/signing-request` - Create signing request
  - `POST /api/hardware-wallet/signing-request/{id}/submit` - Submit signature
  - `GET /api/hardware-wallet/associations` - List user's devices
  - `GET /api/hardware-wallet/supported` - Supported devices/chains

- ✅ **WebSocket Channels**: Real-time streaming
  - `tenant.{tenantId}` - General notifications
  - `tenant.{tenantId}.accounts` - Account updates
  - `tenant.{tenantId}.transactions` - Transaction feed
  - `tenant.{tenantId}.exchange` - Order book/trading updates

### Core API Endpoints
- ✅ **CGO Investment APIs**: Complete investment platform endpoints
  - `POST /api/cgo/investments` - Create investment
  - `GET /api/cgo/investments/{uuid}` - Get investment details
  - `POST /api/cgo/payments/stripe/checkout` - Create Stripe checkout
  - `POST /api/cgo/payments/coinbase/charge` - Create Coinbase charge
  - `POST /api/cgo/webhooks/stripe` - Stripe webhook handler
  - `POST /api/cgo/webhooks/coinbase` - Coinbase webhook handler
  
- ✅ **GCU Trading APIs**: Buy/sell operations
  - `POST /api/gcu/buy` - Buy GCU
  - `POST /api/gcu/sell` - Sell GCU
  - `GET /api/gcu/price` - Get current GCU price
  - `GET /api/gcu/balance` - Get user's GCU balance

- ✅ **Voting System APIs**: Democratic governance
  - `GET /api/polls` - List polls
  - `POST /api/polls` - Create poll
  - `POST /api/polls/{uuid}/vote` - Submit vote
  - `GET /api/polls/{uuid}/results` - Get results

- ✅ **Authentication Enhancements**
  - `POST /api/auth/2fa/enable` - Enable 2FA
  - `POST /api/auth/2fa/verify` - Verify 2FA code
  - `GET /api/auth/oauth/redirect` - OAuth redirect
  - `GET /api/auth/oauth/callback` - OAuth callback

### API Coverage
- **Total Controllers**: 40+
- **Documented Endpoints**: 95%
- **OpenAPI Coverage**: 100% (see OPENAPI_COVERAGE_100_PERCENT.md)
- **Test Coverage**: 88%