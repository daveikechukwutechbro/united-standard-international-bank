<?php

namespace App\Domain\Governance\Activities;

use App\Models\Poll;
use Workflow\Activity;

class GetPollActivity extends Activity
{
    /**
     * Execute get poll activity.
     */
    public function execute(string $pollUuid): ?Poll
    {
        return Poll::where('uuid', $pollUuid)->first();
    }
}
