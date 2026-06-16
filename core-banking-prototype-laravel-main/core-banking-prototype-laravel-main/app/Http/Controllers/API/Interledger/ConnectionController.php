<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Interledger;

use App\Domain\Interledger\Services\IlpConnectorService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ConnectionController extends Controller
{
    public function __construct(
        private readonly IlpConnectorService $connectorService,
    ) {
    }

    /**
     * Create a new ILP STREAM connection.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination_address' => ['required', 'string'],
            'shared_secret'       => ['required', 'string', 'min:32'],
        ]);

        $connection = $this->connectorService->createConnection(
            destinationAddress: $validated['destination_address'],
            sharedSecret: $validated['shared_secret'],
        );

        return response()->json($connection, 201);
    }

    /**
     * Close an existing ILP STREAM connection.
     */
    public function destroy(string $id): Response
    {
        $this->connectorService->closeConnection($id);

        return response()->noContent();
    }
}
