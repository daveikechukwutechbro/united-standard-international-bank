"use client"

import { useQuery, useMutation } from "@apollo/client"
import { GET_ACCOUNT, GET_PAYMENTS } from "@/lib/graphql/queries"
import { FREEZE_ACCOUNT, UNFREEZE_ACCOUNT } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { useParams, useRouter } from "next/navigation"
import { ArrowLeft, Building2, Snowflake, ArrowLeftRight } from "lucide-react"

function AccountDetailContent() {
  const { id } = useParams<{ id: string }>()
  const router = useRouter()

  const { data: accountData, loading: accountLoading } = useQuery(GET_ACCOUNT, { variables: { id } })
  const { data: paymentsData, loading: paymentsLoading } = useQuery(GET_PAYMENTS, { variables: { first: 50 } })

  const [freezeAccount, { loading: freezing }] = useMutation(FREEZE_ACCOUNT, {
    refetchQueries: [{ query: GET_ACCOUNT, variables: { id } }],
  })
  const [unfreezeAccount, { loading: unfreezing }] = useMutation(UNFREEZE_ACCOUNT, {
    refetchQueries: [{ query: GET_ACCOUNT, variables: { id } }],
  })

  const account = accountData?.account
  const allPayments = paymentsData?.payments?.data || []
  const payments = allPayments.filter((p: any) => p.account_uuid === account?.uuid)

  const handleFreeze = async () => {
    if (account?.frozen) {
      await unfreezeAccount({ variables: { id } })
    } else {
      await freezeAccount({ variables: { input: { id } } })
    }
  }

  if (accountLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <div className="h-6 w-6 animate-spin rounded-full border-2 border-blue-500 border-t-transparent" />
      </div>
    )
  }

  if (!account) {
    return (
      <div className="space-y-6">
        <button onClick={() => router.back()} className="flex items-center gap-2 text-sm text-neutral-400 hover:text-white">
          <ArrowLeft size={16} />
          Back
        </button>
        <p className="text-center text-neutral-500">Account not found</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <button onClick={() => router.back()} className="flex items-center gap-2 text-sm text-neutral-400 hover:text-white">
        <ArrowLeft size={16} />
        Back
      </button>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900 p-6">
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-4">
            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600/10 text-blue-400">
              <Building2 size={22} />
            </div>
            <div>
              <h1 className="text-xl font-semibold text-white">{account.name}</h1>
              <p className="text-xs text-neutral-500">{account.uuid}</p>
            </div>
          </div>
          <button
            onClick={handleFreeze}
            disabled={freezing || unfreezing}
            className={`flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium ${
              account.frozen
                ? "border border-green-700 bg-green-600/10 text-green-400 hover:bg-green-600/20"
                : "border border-red-700 bg-red-600/10 text-red-400 hover:bg-red-600/20"
            }`}
          >
            {account.frozen ? <Snowflake size={14} className="text-red-400" /> : <Snowflake size={14} />}
            {account.frozen ? "Unfreeze" : "Freeze"}
          </button>
        </div>

        <div className="mt-6 grid gap-4 sm:grid-cols-3">
          <div className="rounded-lg border border-neutral-800 bg-neutral-950/50 p-4">
            <p className="text-xs text-neutral-500">Balance</p>
            <p className="mt-1 text-2xl font-semibold text-white">
              ${account.balance?.toLocaleString("en-US", { minimumFractionDigits: 2 })}
            </p>
          </div>
          <div className="rounded-lg border border-neutral-800 bg-neutral-950/50 p-4">
            <p className="text-xs text-neutral-500">Status</p>
            <p className="mt-1 flex items-center gap-2 text-sm font-medium">
              {account.frozen ? (
                <>
                  <span className="inline-flex items-center gap-1 rounded-full bg-red-500/10 px-2 py-0.5 text-xs font-medium text-red-400">
                    <Snowflake size={10} />
                    Frozen
                  </span>
                </>
              ) : (
                <span className="rounded-full bg-green-500/10 px-2 py-0.5 text-xs font-medium text-green-400">
                  Active
                </span>
              )}
            </p>
          </div>
          <div className="rounded-lg border border-neutral-800 bg-neutral-950/50 p-4">
            <p className="text-xs text-neutral-500">Created</p>
            <p className="mt-1 text-sm text-white">
              {new Date(account.created_at).toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" })}
            </p>
          </div>
        </div>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Recent Payments</h2>
        </div>
        {paymentsLoading ? (
          <div className="flex justify-center py-12">
            <div className="h-6 w-6 animate-spin rounded-full border-2 border-blue-500 border-t-transparent" />
          </div>
        ) : payments.length === 0 ? (
          <p className="px-5 py-12 text-center text-sm text-neutral-500">No payments for this account</p>
        ) : (
          <div className="divide-y divide-neutral-800">
            {payments.map((p: any) => (
              <div key={p.id} className="flex items-center justify-between px-5 py-3">
                <div className="flex items-center gap-3">
                  <div
                    className={`flex h-8 w-8 items-center justify-center rounded-full ${
                      p.status === "completed"
                        ? "bg-green-500/10 text-green-400"
                        : p.status === "failed"
                          ? "bg-red-500/10 text-red-400"
                          : "bg-yellow-500/10 text-yellow-400"
                    }`}
                  >
                    <ArrowLeftRight size={14} />
                  </div>
                  <div>
                    <p className="text-sm text-white capitalize">{p.type?.replace("_", " ")}</p>
                    <p className="text-xs text-neutral-500">{p.reference || p.id?.slice(0, 8)}</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-sm font-medium text-white">
                    {p.currency} {p.amount?.toLocaleString()}
                  </p>
                  <p
                    className={`text-xs capitalize ${
                      p.status === "completed"
                        ? "text-green-400"
                        : p.status === "failed"
                          ? "text-red-400"
                          : "text-yellow-400"
                    }`}
                  >
                    {p.status}
                  </p>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default function AccountDetailPage() {
  return (
    <AppShell>
      <AccountDetailContent />
    </AppShell>
  )
}
