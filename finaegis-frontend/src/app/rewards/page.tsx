"use client"

import { useQuery } from "@apollo/client"
import { GET_REWARD_PROFILE } from "@/lib/graphql/queries"
import { AppShell } from "@/components/layout/AppShell"
import { Award, Flame, Star, Zap } from "lucide-react"

function RewardsContent() {
  const { data, loading } = useQuery(GET_REWARD_PROFILE)

  const profile = data?.rewardProfile

  if (loading) {
    return (
      <div className="space-y-6">
        <h1 className="text-2xl font-semibold text-white mb-1">Rewards</h1>
        <p className="text-sm text-neutral-400 mb-6">Your loyalty and achievement profile</p>
        <p className="text-sm text-neutral-500">Loading...</p>
      </div>
    )
  }

  if (!profile) {
    return (
      <div className="space-y-6">
        <h1 className="text-2xl font-semibold text-white mb-1">Rewards</h1>
        <p className="text-sm text-neutral-400 mb-6">Your loyalty and achievement profile</p>
        <p className="text-sm text-neutral-500">No reward profile available</p>
      </div>
    )
  }

  const xpPercent = profile.xp_for_next ? Math.min(Math.round((profile.xp_progress / profile.xp_for_next) * 100), 100) : 0

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">Rewards</h1>
        <p className="text-sm text-neutral-400 mb-6">Your loyalty and achievement profile</p>
      </div>

      <div className="rounded-xl border border-neutral-800 bg-neutral-900">
        <div className="border-b border-neutral-800 px-5 py-4">
          <h2 className="text-sm font-medium text-white">Profile</h2>
        </div>
        <div className="px-5 py-6">
          <div className="flex items-center gap-4 mb-6">
            <div className="flex h-14 w-14 items-center justify-center rounded-full bg-blue-600/20 text-blue-400">
              <Star size={24} />
            </div>
            <div>
              <p className="text-2xl font-bold text-white">Level {profile.level}</p>
              <p className="text-sm text-neutral-400">{profile.xp} XP</p>
            </div>
          </div>

          <div className="mb-2 flex items-center justify-between text-xs">
            <span className="text-neutral-400">Level {profile.level}</span>
            <span className="text-neutral-400">{profile.xp_progress} / {profile.xp_for_next} XP</span>
          </div>
          <div className="h-2 rounded-full bg-neutral-800 overflow-hidden">
            <div className="h-full rounded-full bg-blue-600 transition-all duration-500" style={{ width: `${xpPercent}%` }} />
          </div>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <div className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
          <div className="flex items-center gap-3 mb-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-orange-500/10 text-orange-400">
              <Flame size={14} />
            </div>
            <span className="text-xs text-neutral-400">Current Streak</span>
          </div>
          <p className="text-xl font-bold text-white">{profile.current_streak} days</p>
        </div>
        <div className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
          <div className="flex items-center gap-3 mb-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-green-500/10 text-green-400">
              <Zap size={14} />
            </div>
            <span className="text-xs text-neutral-400">Points Balance</span>
          </div>
          <p className="text-xl font-bold text-white">{profile.points_balance}</p>
        </div>
        <div className="rounded-xl border border-neutral-800 bg-neutral-900 p-5">
          <div className="flex items-center gap-3 mb-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-purple-500/10 text-purple-400">
              <Award size={14} />
            </div>
            <span className="text-xs text-neutral-400">Quests Completed</span>
          </div>
          <p className="text-xl font-bold text-white">{profile.quests_completed}</p>
        </div>
      </div>
    </div>
  )
}

export default function RewardsPage() {
  return (
    <AppShell>
      <RewardsContent />
    </AppShell>
  )
}
