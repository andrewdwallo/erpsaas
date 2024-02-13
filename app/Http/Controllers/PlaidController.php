<?php

namespace App\Http\Controllers;

use App\Services\PlaidService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PlaidController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, PlaidService $plaidService)
    {
        $publicTokenResponse = $plaidService->createSandboxPublicToken();
        $publicToken = $publicTokenResponse->public_token;

        $accessTokenResponse = $plaidService->exchangePublicToken($publicToken);
        $accessToken = $accessTokenResponse->access_token;

        $authResponse = $plaidService->getAuth($accessToken);
        $account = $authResponse;

        $institutionId = $account->item->institution_id;

        $institutionResponse = $plaidService->getInstitution($institutionId);

        return response()->json([
            'public_token' => $publicToken,
            'public_token_response' => $publicTokenResponse,
            'access_token_response' => $accessTokenResponse,
            'access_token' => $accessToken,
            'account' => $account,
            'institution' => $institutionResponse,
        ]);
    }
}
