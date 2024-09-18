<?php

namespace ChrisReedIO\ScoutKeys\Http\Controllers;

use ChrisReedIO\ScoutKeys\Contracts\SearchUser;
use ChrisReedIO\ScoutKeys\Models\SearchKey;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

use function abort;
use function auth;
use function is_null;
use function now;
use function response;

class ShowSearchKeyController extends Controller
{
    public function __invoke()
    {
        // Get a search key, if not logged in return a 401
        // If the search key is expired, request a new one
        // If the search key is not expired, return it
        if (! auth()->check()) {
            abort(401);
        }

        /** @var SearchUser $user */
        $user = auth()->user();

        // Check to see if the user implements the SearchUser interface
        if (! $user instanceof SearchUser) {
            // Log::error('User ' . $user->getKey() . ' does not implement the SearchUser interface.');

            return response()->json([
                'message' => 'User does not implement the SearchUser interface',
            ], 500);
        }

        // TODO: Perform Authorization check here
        // if (!$user->can('appointments.dashboard')) {
        //     abort(403);
        // }

        /** @var ?SearchKey $searchKey */
        $searchKey = $user->searchKey()->where('expires_at', '>', now())->first();

        if ($searchKey === null) {
            $searchKey = $user->generateSearchKey();
            $user->refresh();
        }

        if (is_null($searchKey) || is_null($searchKey->scoped_key)) {
            if (is_null($searchKey->request())) {
                Log::error('User '.$user->getKey().' tried to request a search key but failed.');

                return response()->json([
                    'message' => 'Failed to acquire a search key',
                ], 500);
            }
        }

        return response()->json([
            'searchKey' => $searchKey->scoped_key,
        ]);

    }
}
