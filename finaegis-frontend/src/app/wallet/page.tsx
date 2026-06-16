"use client"

import { useState } from "react"
import { useQuery, useMutation } from "@apollo/client"
import { GET_WALLETS, GET_SMART_ACCOUNTS } from "@/lib/graphql/queries"
import { CREATE_WALLET, CREATE_SMART_ACCOUNT, TRANSFER_FUNDS } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"

function WalletContent() {
  const { data: walletsData, refetch: refetchWallets } = useQuery(GET_WALLETS, { variables: { first: 50 } })
  const { data: smartAccountsData, refetch: refetchSmartAccounts } = useQuery(GET_SMART_ACCOUNTS, { variables: { first: 50 } })
  const [createWallet] = useMutation(CREATE_WALLET)
  const [createSmartAccount] = useMutation(CREATE_SMART_ACCOUNT)
  const [transferFunds] = useMutation(TRANSFER_FUNDS)

  const [walletForm, setWalletForm] = useState({ name: "", chain: "ethereum", required_signatures: 2, total_signers: 3 })
  const [accountForm, setAccountForm] = useState({ owner_address: "", network: "ethereum" })
  const [transferForm, setTransferForm] = useState({ from_wallet_id: "", to_address: "", amount: "", chain: "ethereum" })

  const [creatingWallet, setCreatingWallet] = useState(false)
  const [creatingAccount, setCreatingAccount] = useState(false)
  const [transferring, setTransferring] = useState(false)
  const [transferResult, setTransferResult] = useState<string | null>(null)

  const wallets = walletsData?.wallets?.data || []
  const smartAccounts = smartAccountsData?.smartAccounts?.data || []

  const handleCreateWallet = async (e: React.FormEvent) => {
    e.preventDefault()
    setCreatingWallet(true)
    try {
      await createWallet({
        variables: {
          input: {
            name: walletForm.name,
            chain: walletForm.chain,
            required_signatures: walletForm.required_signatures,
            total_signers: walletForm.total_signers,
          },
        },
      })
      setWalletForm({ name: "", chain: "ethereum", required_signatures: 2, total_signers: 3 })
      refetchWallets()
    } catch {
    } finally {
      setCreatingWallet(false)
    }
  }

  const handleCreateSmartAccount = async (e: React.FormEvent) => {
    e.preventDefault()
    setCreatingAccount(true)
    try {
      await createSmartAccount({
        variables: {
          input: {
            owner_address: accountForm.owner_address,
            network: accountForm.network,
          },
        },
      })
      setAccountForm({ owner_address: "", network: "ethereum" })
      refetchSmartAccounts()
    } catch {
    } finally {
      setCreatingAccount(false)
    }
  }

  const handleTransfer = async (e: React.FormEvent) => {
    e.preventDefault()
    setTransferring(true)
    setTransferResult(null)
    try {
      await transferFunds({
        variables: {
          input: {
            from_wallet_id: transferForm.from_wallet_id,
            to_address: transferForm.to_address,
            amount: parseFloat(transferForm.amount),
            chain: transferForm.chain,
          },
        },
      })
      setTransferResult("Transfer initiated successfully")
      setTransferForm({ from_wallet_id: "", to_address: "", amount: "", chain: "ethereum" })
    } catch (err: any) {
      setTransferResult(err.message || "Transfer failed")
    } finally {
      setTransferring(false)
    }
  }

  const statusBadge = (status: string) => {
    const styles: Record<string, string> = {
      active: "bg-green-500/10 text-green-400",
      inactive: "bg-neutral-500/10 text-neutral-400",
      pending: "bg-yellow-500/10 text-yellow-400",
      locked: "bg-red-500/10 text-red-400",
    }
    return `rounded-full px-2 py-0.5 text-xs font-medium ${styles[status] || "bg-neutral-500/10 text-neutral-400"}`
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white">Wallets</h1>
        <p className="text-sm text-neutral-400">Manage multi-signature wallets and smart accounts</p>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="rounded-xl border border-neutral-800 bg-neutral-900">
          <div className="border-b border-neutral-800 px-5 py-4">
            <h2 className="text-sm font-medium text-white">Create Wallet</h2>
          </div>
          <form onSubmit={handleCreateWallet} className="p-5 space-y-4">
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Wallet Name</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="My MultiSig"
                value={walletForm.name}
                onChange={(e) => setWalletForm({ ...walletForm, name: e.target.value })}
                required
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Chain</label>
              <select
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                value={walletForm.chain}
                onChange={(e) => setWalletForm({ ...walletForm, chain: e.target.value })}
              >
                <option value="ethereum">Ethereum</option>
                <option value="polygon">Polygon</option>
                <option value="arbitrum">Arbitrum</option>
                <option value="optimism">Optimism</option>
                <option value="base">Base</option>
                <option value="solana">Solana</option>
              </select>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="mb-1.5 block text-xs font-medium text-neutral-400">Required Signatures</label>
                <input
                  className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  type="number"
                  min={1}
                  value={walletForm.required_signatures}
                  onChange={(e) => setWalletForm({ ...walletForm, required_signatures: parseInt(e.target.value) })}
                  required
                />
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-medium text-neutral-400">Total Signers</label>
                <input
                  className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  type="number"
                  min={1}
                  value={walletForm.total_signers}
                  onChange={(e) => setWalletForm({ ...walletForm, total_signers: parseInt(e.target.value) })}
                  required
                />
              </div>
            </div>
            <button
              type="submit"
              disabled={creatingWallet}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
            >
              {creatingWallet ? "Creating..." : "Create Wallet"}
            </button>
          </form>
        </div>

        <div className="rounded-xl border border-neutral-800 bg-neutral-900">
          <div className="border-b border-neutral-800 px-5 py-4">
            <h2 className="text-sm font-medium text-white">Transfer Funds</h2>
          </div>
          <form onSubmit={handleTransfer} className="p-5 space-y-4">
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">From Wallet</label>
              <select
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                value={transferForm.from_wallet_id}
                onChange={(e) => setTransferForm({ ...transferForm, from_wallet_id: e.target.value })}
                required
              >
                <option value="">Select wallet</option>
                {wallets.map((w: any) => (
                  <option key={w.id} value={w.id}>{w.name} ({w.chain})</option>
                ))}
              </select>
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">To Address</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="0x..."
                value={transferForm.to_address}
                onChange={(e) => setTransferForm({ ...transferForm, to_address: e.target.value })}
                required
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Amount</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                type="number"
                step="any"
                placeholder="0.00"
                value={transferForm.amount}
                onChange={(e) => setTransferForm({ ...transferForm, amount: e.target.value })}
                required
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Chain</label>
              <select
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                value={transferForm.chain}
                onChange={(e) => setTransferForm({ ...transferForm, chain: e.target.value })}
              >
                <option value="ethereum">Ethereum</option>
                <option value="polygon">Polygon</option>
                <option value="arbitrum">Arbitrum</option>
                <option value="optimism">Optimism</option>
                <option value="base">Base</option>
                <option value="solana">Solana</option>
              </select>
            </div>
            <button
              type="submit"
              disabled={transferring}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
            >
              {transferring ? "Transferring..." : "Transfer Funds"}
            </button>
            {transferResult && (
              <p className={`text-xs ${transferResult === "Transfer initiated successfully" ? "text-green-400" : "text-red-400"}`}>
                {transferResult}
              </p>
            )}
          </form>
        </div>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900 mb-6">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Wallets</h2>
        </div>
        {wallets.length === 0 ? (
          <p className="px-5 py-8 text-center text-sm text-neutral-500">No wallets yet</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {wallets.map((w: any) => (
              <div key={w.id} className="px-5 py-4">
                <div className="flex items-center justify-between mb-2">
                  <div>
                    <p className="text-sm font-medium text-white">{w.name}</p>
                    <p className="text-xs text-neutral-500 font-mono">{w.address}</p>
                  </div>
                  <span className={statusBadge(w.status)}>{w.status}</span>
                </div>
                <div className="flex items-center gap-4 text-xs text-neutral-400">
                  <span className="rounded-full bg-neutral-800 px-2 py-0.5">{w.chain}</span>
                  {w.required_signatures != null && w.total_signers != null && (
                    <span>{w.required_signatures}/{w.total_signers} signatures required</span>
                  )}
                  <span>Created {new Date(w.created_at).toLocaleDateString()}</span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="rounded-xl border border-neutral-800 bg-neutral-900">
          <div className="border-b border-neutral-800 px-5 py-4">
            <h2 className="text-sm font-medium text-white">Smart Accounts</h2>
          </div>
          {smartAccounts.length === 0 ? (
            <p className="px-5 py-8 text-center text-sm text-neutral-500">No smart accounts</p>
          ) : (
            <div className="divide-y divide-neutral-800">
              {smartAccounts.map((a: any) => (
                <div key={a.id} className="px-5 py-4">
                  <div className="flex items-center justify-between mb-2">
                    <div>
                      <p className="text-sm font-medium text-white">Account {a.account_address?.slice(0, 10)}...</p>
                      <p className="text-xs text-neutral-500 font-mono">{a.account_address}</p>
                    </div>
                    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${a.deployed ? "bg-green-500/10 text-green-400" : "bg-yellow-500/10 text-yellow-400"}`}>
                      {a.deployed ? "Deployed" : "Not Deployed"}
                    </span>
                  </div>
                  <div className="flex items-center gap-4 text-xs text-neutral-400">
                    <span className="rounded-full bg-neutral-800 px-2 py-0.5">{a.network}</span>
                    <span>Owner: {a.owner_address?.slice(0, 10)}...</span>
                    <span>Nonce: {a.nonce}</span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="rounded-xl border border-neutral-800 bg-neutral-900">
          <div className="border-b border-neutral-800 px-5 py-4">
            <h2 className="text-sm font-medium text-white">Create Smart Account</h2>
          </div>
          <form onSubmit={handleCreateSmartAccount} className="p-5 space-y-4">
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Owner Address</label>
              <input
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder="0x..."
                value={accountForm.owner_address}
                onChange={(e) => setAccountForm({ ...accountForm, owner_address: e.target.value })}
                required
              />
            </div>
            <div>
              <label className="mb-1.5 block text-xs font-medium text-neutral-400">Network</label>
              <select
                className="w-full rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                value={accountForm.network}
                onChange={(e) => setAccountForm({ ...accountForm, network: e.target.value })}
              >
                <option value="ethereum">Ethereum</option>
                <option value="polygon">Polygon</option>
                <option value="arbitrum">Arbitrum</option>
                <option value="optimism">Optimism</option>
                <option value="base">Base</option>
              </select>
            </div>
            <button
              type="submit"
              disabled={creatingAccount}
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
            >
              {creatingAccount ? "Creating..." : "Create Smart Account"}
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}

export default function WalletPage() {
  return (
    <AppShell>
      <WalletContent />
    </AppShell>
  )
}
