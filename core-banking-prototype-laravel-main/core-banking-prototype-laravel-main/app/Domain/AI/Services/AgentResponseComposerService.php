<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\ValueObjects\AgentResult;

class AgentResponseComposerService
{
    /**
     * Compose a final response from one or more agent results.
     *
     * @param  array<AgentResult>  $agentResults
     * @return array<string, mixed>
     */
    public function compose(array $agentResults, string $originalQuery): array
    {
        if ($agentResults === []) {
            return $this->emptyResponse($originalQuery);
        }

        if (count($agentResults) === 1) {
            return $this->singleAgentResponse($agentResults[0]);
        }

        return $this->multiAgentResponse($agentResults);
    }

    /**
     * @return array<string, mixed>
     */
    private function singleAgentResponse(AgentResult $result): array
    {
        return [
            'content'     => $result->content,
            'confidence'  => $result->confidence,
            'tools_used'  => $result->toolsUsed,
            'agents_used' => [$result->agentName],
            'metadata'    => $result->metadata,
        ];
    }

    /**
     * @param  array<AgentResult>  $results
     * @return array<string, mixed>
     */
    private function multiAgentResponse(array $results): array
    {
        $sections = [];
        $allTools = [];
        $agentNames = [];
        $maxConfidence = 0.0;

        foreach ($results as $result) {
            $sections[] = "**{$result->agentName}**\n{$result->content}";
            $allTools = array_merge($allTools, $result->toolsUsed);
            $agentNames[] = $result->agentName;
            $maxConfidence = max($maxConfidence, $result->confidence);
        }

        return [
            'content'     => implode("\n\n---\n\n", $sections),
            'confidence'  => round($maxConfidence, 2),
            'tools_used'  => array_values(array_unique($allTools)),
            'agents_used' => $agentNames,
            'metadata'    => ['multi_agent' => true, 'agent_count' => count($results)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResponse(string $query): array
    {
        return [
            'content'     => "I understand your query: \"{$query}\". I'm currently unable to process this request. Please try one of the suggested scenarios.",
            'confidence'  => 0.3,
            'tools_used'  => [],
            'agents_used' => [],
            'metadata'    => ['fallback' => true],
        ];
    }
}
