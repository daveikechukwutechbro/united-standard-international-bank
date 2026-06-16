<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Agents\BaseAgent;

class AgentRouterService
{
    /** @var array<string, BaseAgent> */
    private array $agents = [];

    /**
     * @return array<BaseAgent>
     */
    public function route(string $query): array
    {
        $scores = $this->getAgentScores($query);

        // Filter agents above threshold
        $qualified = array_filter($scores, fn (int $score) => $score >= 1);

        if ($qualified === []) {
            // Return GeneralAgent as fallback
            foreach ($this->agents as $agent) {
                if (str_contains(strtolower($agent->getName()), 'general')) {
                    return [$agent];
                }
            }

            // If no general agent, return last registered
            $all = array_values($this->agents);

            return $all !== [] ? [end($all)] : [];
        }

        // Sort by score descending
        arsort($qualified);

        // Cap at 3 agents
        $topNames = array_slice(array_keys($qualified), 0, 3);

        return array_map(fn (string $name) => $this->agents[$name], $topNames);
    }

    public function registerAgent(BaseAgent $agent): void
    {
        $this->agents[$agent->getName()] = $agent;
    }

    /**
     * @return array<string, int>
     */
    public function getAgentScores(string $query): array
    {
        $normalized = strtolower(preg_replace('/[^\w\s]/', '', $query) ?? $query);
        $words = preg_split('/\s+/', $normalized) ?: [];
        $scores = [];

        foreach ($this->agents as $name => $agent) {
            $score = 0;
            $keywords = $agent->getKeywords();

            foreach ($keywords as $keyword) {
                $lowerKeyword = strtolower($keyword);

                // Exact word match = 2 points
                if (in_array($lowerKeyword, $words, true)) {
                    $score += 2;

                    continue;
                }

                // Partial/substring match = 1 point
                if (str_contains($normalized, $lowerKeyword)) {
                    $score += 1;
                }
            }

            $scores[$name] = $score;
        }

        return $scores;
    }
}
