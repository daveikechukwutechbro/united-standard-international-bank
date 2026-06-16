# Mobile App API Compatibility Report

**Audited**: 2026-03-28
**Backend version**: v7.0.0 (commit `91505783`)
**Mobile app**: `finaegis-mobile` (Expo/React Native)
**Base URL**: `https://zelta.app` (configurable via `EXPO_PUBLIC_API_URL`)

---

## Summary

| Category | Count |
|----------|-------|
| Compatible Endpoints | 97 |
| Potentially Breaking | 5 |
| Missing from Backend | 1 |
| **Total endpoints audited** | **103** |

---

## Compatible Endpoints (97)

### Authentication (12)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/auth/login` | POST | `authService.login` | OK |
| `/api/auth/register` | POST | `authService.register` | OK |
| `/api/auth/user` | GET | `authService.getCurrentUser` | OK |
| `/api/auth/logout` | POST | `authService.logout` | OK |
| `/api/auth/refresh` | POST | `authService.refreshTokens` / token interceptor | OK |
| `/api/auth/delete-account` | POST | `authService.deleteAccount` | OK |
| `/api/auth/sign-userop` | POST | `authService.signUserOp` | OK |
| `/api/v1/auth/passkey/challenge` | POST | `authService.getPasskeyChallenge` | OK |
| `/api/v1/auth/passkey/authenticate` | POST | `authService.verifyPasskey` | OK |
| `/api/v1/auth/passkey/register` | POST | `authService.registerPasskey` | OK |
| `/api/v1/auth/passkey/register-challenge` | POST | `authService.getRegistrationChallenge` | OK |
| `/broadcasting/auth` | POST | `wsService` (Pusher authorizer) | OK |

### Wallet (14)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/wallet/tokens` | GET | `walletService.getTokens` | OK |
| `/api/v1/wallet/balances` | GET | `walletService.getBalances` | OK |
| `/api/v1/wallet/state` | GET | `walletService.getWalletState` | OK |
| `/api/v1/wallet/addresses` | GET | `walletService.getAddresses` | OK |
| `/api/v1/wallet/transactions` | GET | `walletService.getTransactions` | OK |
| `/api/v1/wallet/transactions/{id}` | GET | `walletService.getTransaction` | OK |
| `/api/v1/wallet/transactions/send` | POST | `walletService.sendTransaction` | OK |
| `/api/v1/wallet/transactions/quote` | POST | `walletService.getTransactionQuote` | OK |
| `/api/v1/wallet/validate-address` | GET | `walletService.validateAddress` | OK |
| `/api/v1/wallet/resolve-name` | POST | `walletService.resolveName` | OK |
| `/api/v1/wallet/quote` | POST | `walletService.getQuote` | OK |
| `/api/v1/wallet/recent-recipients` | GET | `walletService.getRecentRecipients` | OK |
| `/api/v1/wallet/recovery-shard-backup` | POST | `recoveryBackupService.createOrUpdate` | OK |
| `/api/v1/wallet/recovery-shard-backup` | GET | `recoveryBackupService.list` | OK |

### Wallet (Recovery Shard - continued) (2)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/wallet/recovery-shard-backup` | DELETE | `recoveryBackupService.remove` | OK |
| `/api/v1/wallet/recovery-shard-backup/retrieve` | GET | `recoveryBackupService.retrieve` | OK |

### Payment Intents (4)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/payments/intents` | POST | `paymentsService.createIntent` | OK |
| `/api/v1/payments/intents/{intentId}` | GET | `paymentsService.getIntentStatus` | OK |
| `/api/v1/payments/intents/{intentId}/submit` | POST | `paymentsService.submitIntent` | OK |
| `/api/v1/payments/intents/{intentId}/cancel` | POST | `paymentsService.cancelIntent` | OK |

### Activity & Receipts (3)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/activity` | GET | `paymentsService.getActivity` | OK |
| `/api/v1/transactions/{txId}/receipt` | GET | `paymentsService.getReceipt` | OK |
| `/api/v1/transactions/{txId}/receipt` | POST | `walletService.generateReceipt` | OK |

### Network Status (1)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/networks/{network}/status` | GET | `paymentsService.getNetworkStatus` | OK |

### Cards (8)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/cards` | GET | `cardService.getCards` | OK |
| `/api/v1/cards` | POST | `cardService.createCard` | OK |
| `/api/v1/cards/{cardId}` | GET | `cardService.getCard` | OK |
| `/api/v1/cards/{cardId}` | DELETE | `cardService.cancelCard` | OK |
| `/api/v1/cards/{cardId}/transactions` | GET | `cardService.getCardTransactions` | OK |
| `/api/v1/cards/{cardId}/freeze` | POST | `cardService.freezeCard` | OK |
| `/api/v1/cards/{cardId}/freeze` | DELETE | `cardService.unfreezeCard` | OK |
| `/api/v1/cards/provision` | POST | `cardService.provisionCard` | OK |

### Rewards (5)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/rewards/profile` | GET | `rewardsService.getProfile` | OK |
| `/api/v1/rewards/quests` | GET | `rewardsService.getQuests` | OK |
| `/api/v1/rewards/quests/{id}/complete` | POST | `rewardsService.completeQuest` | OK |
| `/api/v1/rewards/shop` | GET | `rewardsService.getShopItems` | OK |
| `/api/v1/rewards/shop/{id}/redeem` | POST | `rewardsService.redeemItem` | OK |

### Privacy (17)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/privacy/balances` | GET | `privacyService.getShieldedBalances` | OK |
| `/api/v1/privacy/total-balance` | GET | `privacyService.getTotalShieldedBalance` | OK |
| `/api/v1/privacy/transactions` | GET | `privacyService.getPrivacyTransactions` | OK |
| `/api/v1/privacy/merkle-root` | GET | `privacyService.getMerkleRoot` | OK |
| `/api/v1/privacy/merkle-path` | POST | `privacyService.getMerklePath` | OK |
| `/api/v1/privacy/shield` | POST | `privacyService.shield` | OK |
| `/api/v1/privacy/unshield` | POST | `privacyService.unshield` | OK |
| `/api/v1/privacy/transfer` | POST | `privacyService.privateTransfer` | OK |
| `/api/v1/privacy/viewing-key` | GET | `privacyService.getViewingKey` | OK |
| `/api/v1/privacy/proof-of-innocence` | POST | `privacyService.generateProofOfInnocence` | OK |
| `/api/v1/privacy/proof-of-innocence/{proofId}/verify` | GET | `privacyService.verifyProofOfInnocence` | OK |
| `/api/v1/privacy/srs-url` | GET | `privacyService.getSRSDownloadUrl` | OK |
| `/api/v1/privacy/srs-status` | GET | `privacyService.checkSRSStatus` | OK |
| `/api/v1/privacy/srs-manifest` | GET | `privacyService.getSRSManifest` | OK |
| `/api/v1/privacy/delegated-proof` | POST | `privacyService.requestDelegatedProof` | OK |
| `/api/v1/privacy/delegated-proof/{proofId}` | GET | `privacyService.getDelegatedProofStatus` | OK |
| `/api/v1/privacy/networks` | GET | `privacyService.getPrivacyNetworks` | OK |

### Privacy (continued) (3)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/privacy/transaction-calldata/{txHash}` | GET | `privacyService.getTransactionCalldata` | OK |
| `/api/v1/privacy/transactions/{transactionId}/tx-hash` | PUT | `privacyService.updateTransactionHash` | OK |
| `/api/v1/privacy/pool-stats` | GET | `privacyService.getPoolStats` | OK |

### Commerce (8)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/commerce/merchants` | GET | `commerceService.getMerchants` | OK |
| `/api/v1/commerce/merchants/{merchantId}` | GET | `commerceService.getMerchant` | OK |
| `/api/v1/commerce/parse-qr` | POST | `commerceService.parseQRCode` | OK |
| `/api/v1/commerce/payment-requests` | POST | `commerceService.createPaymentRequest` | OK |
| `/api/v1/commerce/payment-requests/{paymentId}` | GET | `commerceService.getPaymentRequest` | OK |
| `/api/v1/commerce/payment-requests/{paymentId}/cancel` | POST | `commerceService.cancelPayment` | OK |
| `/api/v1/commerce/payments` | POST | `commerceService.processPayment` | OK |
| `/api/v1/commerce/payments/recent` | GET | `commerceService.getRecentPayments` | OK |

### Commerce (continued) (1)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/commerce/generate-qr` | POST | `commerceService.generateQRCode` | OK |

### Ramp (On/Off) (5)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/ramp/supported` | GET | `rampService.getSupported` | OK |
| `/api/v1/ramp/quotes` | GET | `rampService.getQuotes` | OK |
| `/api/v1/ramp/session` | POST | `rampService.createSession` | OK |
| `/api/v1/ramp/session/{id}` | GET | `rampService.getSession` | OK |
| `/api/v1/ramp/sessions` | GET | `rampService.listSessions` | OK |

### Banners (2)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/banners` | GET | `bannerService.getBanners` | OK |
| `/api/v1/banners/{id}/dismiss` | POST | `bannerService.dismissBanner` | OK |

### Referrals (4)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/referrals/my-code` | GET | `referralService.getMyCode` | OK |
| `/api/v1/referrals/apply` | POST | `referralService.applyCode` | OK |
| `/api/v1/referrals` | GET | `referralService.listReferrals` | OK |
| `/api/v1/referrals/stats` | GET | `referralService.getStats` | OK |

### Sponsorship (1)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/sponsorship/status` | GET | `sponsorshipService.getStatus` | OK |

### TrustCert / KYC (11)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/trustcert/current` | GET | `trustCertService.getCurrentCert` | OK |
| `/api/v1/trustcert/requirements` | GET | `trustCertService.getLevelRequirements` | OK |
| `/api/v1/trustcert/requirements/{level}` | GET | `trustCertService.getLevelRequirement` | OK |
| `/api/v1/trustcert/limits` | GET | `trustCertService.getTransactionLimits` | OK |
| `/api/v1/trustcert/check-limit` | POST | `trustCertService.checkTransactionAllowed` | OK |
| `/api/v1/trustcert/applications` | POST | `trustCertService.startApplication` | OK |
| `/api/v1/trustcert/applications/current` | GET | `trustCertService.getCurrentApplication` | OK |
| `/api/v1/trustcert/applications/{id}` | GET | `trustCertService.getApplication` | OK |
| `/api/v1/trustcert/applications/{id}/documents` | POST | `trustCertService.uploadDocument` | OK |
| `/api/v1/trustcert/applications/{id}/submit` | POST | `trustCertService.submitApplication` | OK |
| `/api/v1/trustcert/applications/{id}/cancel` | POST | `trustCertService.cancelApplication` | OK |

### TrustCert (continued) (1)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/trustcert/verify/{certId}` | GET | `trustCertService.verifyCert` | OK |

### Ondato KYC (2)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/compliance/kyc/ondato/start` | POST | `ondatoService.startSession` | OK |
| `/api/compliance/kyc/ondato/status/{verificationId}` | GET | `ondatoService.getStatus` | OK |

### Compliance (1)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/compliance/check-address` | GET | `complianceService.checkAddress` | OK |

### Notifications (3)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/notifications` | GET | `notificationService.getNotifications` | OK |
| `/api/v1/notifications/unread-count` | GET | `notificationService.getUnreadCount` | OK |
| `/api/v1/notifications/{id}/read` | POST | `notificationService.markAsRead` | OK -- see note below |

### User Preferences (2)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/user/preferences` | GET | `preferencesService.getPreferences` | OK |
| `/api/v1/user/preferences` | PATCH | `preferencesService.updatePreferences` | OK |

### Data Export (2)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/user/data-export` | POST | `walletService.requestDataExportAsync` | OK |
| `/api/v1/user/data-export/{exportId}` | GET | `walletService.getDataExportStatus` | OK |

### Avatar (2)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/users/avatar` | POST | `avatarService.uploadAvatar` | OK |
| `/api/v1/users/avatar` | DELETE | `avatarService.deleteAvatar` | OK |

### SSL Pins (1)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/mobile/ssl-pins` | GET | `sslPinsService.getPins` | OK |

### x402 Protocol (10)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/x402/status` | GET | `x402Service.getStatus` | OK |
| `/api/v1/x402/supported` | GET | `x402Service.getSupported` | OK |
| `/api/v1/x402/spending-limits` | GET | `x402Service.getSpendingLimits` | OK |
| `/api/v1/x402/spending-limits` | POST | `x402Service.createOrUpdateSpendingLimit` | OK |
| `/api/v1/x402/spending-limits/{agentId}` | GET | `x402Service.getSpendingLimit` | OK |
| `/api/v1/x402/spending-limits/{agentId}` | PUT | `x402Service.updateSpendingLimit` | OK |
| `/api/v1/x402/spending-limits/{agentId}` | DELETE | `x402Service.deleteSpendingLimit` | OK |
| `/api/v1/x402/payments` | GET | `x402Service.getPayments` | OK |
| `/api/v1/x402/payments/stats` | GET | `x402Service.getPaymentStats` | OK |
| `/api/v1/x402/payments/{id}` | GET | `x402Service.getPayment` | OK |

### x402 (continued) (1)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/x402/endpoints` | GET | `x402Service.getEndpoints` | OK |

### MPP (Machine Payments Protocol) (3)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/mpp/status` | GET | `mppService.getStatus` | OK |
| `/api/v1/mpp/supported-rails` | GET | `mppService.getSupportedRails` | OK |
| `/api/v1/mpp/payments/stats` | GET | `mppService.getPaymentStats` | OK |

### Relayer / Smart Account (10)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/relayer/account` | GET | `relayerService.getSmartAccount` | OK |
| `/api/v1/relayer/account` | POST | `relayerService.createSmartAccount` | OK |
| `/api/v1/relayer/nonce/{address}` | GET | `relayerService.getNonce` | OK |
| `/api/v1/relayer/status` | GET | `relayerService.getRelayerStatus` | OK |
| `/api/v1/relayer/estimate-fee` | POST | `relayerService.estimateGas` | OK |
| `/api/v1/relayer/build-userop` | POST | `relayerService.buildUserOperation` | OK |
| `/api/v1/relayer/submit` | POST | `relayerService.submitUserOperation` | OK |
| `/api/v1/relayer/userop/{hash}` | GET | `relayerService.getUserOperationStatus` | OK |
| `/api/v1/relayer/supported-tokens` | GET | `relayerService.getSupportedTokensForGas` | OK |
| `/api/v1/relayer/networks/{network}/status` | GET | `relayerService.getNetworkStatus` | OK |

### Relayer (continued) (1)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/relayer/sponsor` | POST | `relayerService.sponsorUserOperation` | OK |

### Virtuals Agents (6)

| Endpoint | Method | Mobile Service | Status |
|----------|--------|----------------|--------|
| `/api/v1/virtuals-agents` | GET | `agentService.getAgents` | OK |
| `/api/v1/virtuals-agents/{id}` | GET | `agentService.getAgent` | OK |
| `/api/v1/virtuals-agents/onboard` | POST | `agentService.onboardAgent` | OK |
| `/api/v1/virtuals-agents/{id}/suspend` | PUT | `agentService.suspendAgent` | OK |
| `/api/v1/virtuals-agents/{id}/activate` | PUT | `agentService.activateAgent` | OK |
| `/api/v1/virtuals-agents/{id}/transactions` | GET | `agentService.getSpendingSummary` | OK |

---

## Potentially Breaking (5)

| # | Endpoint | Method | Mobile Service | Issue |
|---|----------|--------|----------------|-------|
| 1 | `/api/v1/notifications/mark-all-read` | POST | `notificationService.markAllAsRead` | **Route mismatch.** Mobile calls `POST /api/v1/notifications/mark-all-read` but backend route is `POST /api/v1/notifications/read-all`. The mobile will receive a 404. |
| 2 | `/api/v1/notifications/{id}/read` | PATCH | `notificationService.markAsRead` | **HTTP method mismatch.** Mobile sends `PATCH` (via `patch()` helper), but backend registers `POST`. Depending on method enforcement, this may work (some Laravel setups accept POST for PATCH) or return 405 Method Not Allowed. |
| 3 | `/api/v1/relayer/paymaster-data` | POST | `relayerService.getPaymasterData` | **HTTP method mismatch.** Mobile sends `POST` but backend registers `GET` for this route. Will return 405 Method Not Allowed. |
| 4 | `/api/v1/auth/sign-userop` | POST | `authService.signUserOp` | **Path prefix discrepancy.** Mobile calls `/api/v1/auth/sign-userop` but the backend route is `/api/auth/sign-userop` (no `v1` prefix). The mobile will get a 404 unless there is a fallback redirect. |
| 5 | `/api/v1/rewards/quests/{id}/complete` + `/api/v1/rewards/shop/{id}/redeem` | POST | `rewardsService.completeQuest` / `redeemItem` | **UUID constraint.** Backend has `whereUuid('id')` on both routes. Mobile sends plain string IDs (e.g. `"1"`, `"quest_abc"`) from mock data. Non-UUID IDs will return 404 in production. This is fine if the backend always returns UUIDs in list responses, but mobile must not hardcode non-UUID test IDs. |

---

## Missing from Backend (1)

| # | Endpoint | Method | Mobile Service | Notes |
|---|----------|--------|----------------|-------|
| 1 | `/api/v1/devices` | GET | `deviceService.getDevices` | Mobile calls `GET /api/v1/devices`, `DELETE /api/v1/devices/{id}`, and `DELETE /api/v1/devices/all`. Backend has these under `GET /api/mobile/devices`, `DELETE /api/mobile/devices/{id}`, and `DELETE /api/mobile/devices/all`. There is no `/api/v1/devices` prefix registered. Mobile will get 404 for all device management calls. |

---

## WebSocket Channels (7) -- All Compatible

The mobile WebSocket service connects via Pusher/Soketi with bearer token authorization
at `/broadcasting/auth`. The following private channels are subscribed:

| Channel Pattern | Events | Status |
|----------------|--------|--------|
| `private-user.{userId}` | `transaction.confirmed`, `transaction.failed`, `notification.count_updated` | OK |
| `private-privacy.{userId}` | `privacy.operation.completed`, `privacy.balance_updated` | OK |
| `private-privacy.proof.{userId}` | `proof.ready`, `proof.failed` | OK |
| `private-wallet.{userId}` | `wallet.balance_updated`, `wallet.state_changed` | OK |
| `private-commerce.{merchantId}` | `commerce.payment.confirmed` | OK |
| `private-trustcert.{userId}` | `trustcert.status.changed` | OK |

---

## Recommendations

### Critical (fix before mobile release)

1. **Notifications mark-all-read path**: Either add a backend alias route at `/api/v1/notifications/mark-all-read` pointing to `markAllRead`, or update the mobile to call `/api/v1/notifications/read-all`.

2. **Notifications mark-as-read method**: Either change the backend route from `POST` to accept both `POST` and `PATCH`, or update the mobile `notificationService.markAsRead` to use `post()` instead of `patch()`.

3. **Device management path prefix**: Add backend route aliases under `/api/v1/devices` that proxy to the existing `/api/mobile/devices` controllers, or update the mobile `deviceService` to use the `/api/mobile/devices` path prefix.

4. **Relayer paymaster-data method**: Either change the backend `GET` route for `/api/v1/relayer/paymaster-data` to accept `POST`, or update the mobile `relayerService.getPaymasterData` to use `get()`.

5. **Auth sign-userop path**: Either add a backend alias route at `/api/v1/auth/sign-userop`, or update the mobile `authService.signUserOp` to call `/api/auth/sign-userop`.

### Low Priority

6. **Rewards UUID constraint**: Ensure the mobile never sends non-UUID quest/shop item IDs. The mock data uses simple strings which won't pass `whereUuid()` validation. Production data should be fine since the backend generates UUIDs.

---

## Methodology

- Extracted all API endpoint URLs from the mobile `src/services/api/*.ts` files (real implementations only, mocks excluded)
- Cross-referenced against backend route files: `routes/api.php`, `routes/api-v2.php`, and all `app/Domain/*/Routes/api.php` files
- Verified HTTP method (GET/POST/PUT/PATCH/DELETE) matches
- Checked for route constraints (`whereUuid`, middleware) that could cause silent 404s
- Reviewed WebSocket channel subscriptions against Laravel broadcasting configuration
